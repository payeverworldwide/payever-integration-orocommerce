<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Payever\Bundle\PaymentBundle\Service\Helper\CompareHelper;
use Payever\Bundle\PaymentBundle\Service\Helper\TransactionHelper;
use Payever\Bundle\PaymentBundle\Service\Management\OrderManager;
use Payever\Sdk\Payments\Enum\Status;
use Payever\Sdk\Payments\Http\MessageEntity\RetrievePaymentResultEntity;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class TransactionStatusService
{
    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;

    /**
     * @var PaymentTransactionProvider
     */
    private PaymentTransactionProvider $paymentTransactionProvider;

    /**
     * @var TransactionHelper
     */
    private TransactionHelper $transactionHelper;

    /**
     * @var OrderManager
     */
    private OrderManager $orderManager;

    /**
     * @var RequestStack
     */
    private RequestStack $requestStack;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Constructor.
     *
     * @param EntityManager $entityManager
     * @param PaymentTransactionProvider $paymentTransactionProvider
     * @param TransactionHelper $transactionHelper
     * @param OrderManager $orderManager
     * @param RequestStack $requestStack
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntityManager $entityManager,
        PaymentTransactionProvider $paymentTransactionProvider,
        TransactionHelper $transactionHelper,
        OrderManager $orderManager,
        RequestStack $requestStack,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
        $this->transactionHelper = $transactionHelper;
        $this->orderManager = $orderManager;
        $this->requestStack = $requestStack;
        $this->logger = $logger;
    }

    /**
     * Persist Transaction Status.
     *
     * @param RetrievePaymentResultEntity $payeverPayment
     * @return void
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Throwable
     */
    public function persistTransactionStatus(RetrievePaymentResultEntity $payeverPayment): void
    {
        $paymentId = $payeverPayment->getId();
        $orderReference = (string) $payeverPayment->getReference();
        $order = $this->transactionHelper->getOrderByIdentifier($orderReference);
        if (!$order) {
            throw new \UnexpectedValueException('Order is not found');
        }

        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $this->getInitialTransaction($order);
        if (!$paymentTransaction) {
            throw new \UnexpectedValueException('Payment transaction is missing for Order #' . $order->getId());
        }

        // Allocate order items
        $this->orderManager->allocateOrderItems($order);

        // Validate amount
        $this->validateTransactionAmount(
            $paymentTransaction,
            (float) $payeverPayment->getTotal() - (float) $payeverPayment->getPaymentFee()
        );

        switch ($payeverPayment->getStatus()) {
            case Status::STATUS_IN_PROCESS:
            case Status::STATUS_ACCEPTED:
                // Save payment details
                $this->transactionHelper->updateTransactionOptions($paymentTransaction, $payeverPayment->toArray());

                $paymentTransaction->setAction(PaymentMethodInterface::AUTHORIZE)
                    ->setReference($paymentId)
                    ->setSuccessful(true)
                    ->setActive(true);

                $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);

                break;
            case Status::STATUS_PAID:
                // Save payment details
                $this->transactionHelper->updateTransactionOptions($paymentTransaction, $payeverPayment->toArray());

                $paymentTransaction->setAction(PaymentMethodInterface::CAPTURE)
                    ->setReference($paymentId)
                    ->setSuccessful(true)
                    ->setActive(false);

                $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);

                // Mark order items captured
                $this->orderManager->shipOrderItems(
                    $order,
                    $this->orderManager->getOrderQtyAvailableForCapture($order)
                );

                break;
        }
    }

    /**
     * Checks if Notification will be rejected.
     *
     * @param string $reference
     * @param int $notificationTimestamp
     * @return bool
     */
    public function shouldRejectNotification(
        string $reference,
        int $notificationTimestamp
    ): bool {
        $paymentTransaction = $this->getPaymentTransactionByOrderReference($reference);
        if (!$paymentTransaction) {
            throw new \UnexpectedValueException('Payment transaction is missing for Order #' . $reference);
        }

        $transactionOptions = $paymentTransaction->getTransactionOptions();
        $lastTimestamp = $transactionOptions['notificationTimestamp'] ?? 0;

        return ($lastTimestamp > $notificationTimestamp);
    }

    /**
     * Update Notification Timestamp.
     *
     * @param $reference
     * @param int $notificationTimestamp
     * @return void
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateNotificationTimestamp(
        $reference,
        int $notificationTimestamp
    ): void {
        $paymentTransaction = $this->getPaymentTransactionByOrderReference($reference);
        if (!$paymentTransaction) {
            throw new \UnexpectedValueException('Payment transaction is missing for Order #' . $reference);
        }

        $transactionOptions = $paymentTransaction->getTransactionOptions();
        $transactionOptions['notificationTimestamp'] = $notificationTimestamp;

        $paymentTransaction->setTransactionOptions($transactionOptions);

        $this->entityManager->persist($paymentTransaction);
        $this->entityManager->flush($paymentTransaction);
    }

    private function validateTransactionAmount(PaymentTransaction $paymentTransaction, float $transactionAmount): void
    {
        // Verify transaction amount
        if (!CompareHelper::areSame($transactionAmount, (float) $paymentTransaction->getAmount())) {
            // @todo Trigger payment cancel/refund
            $this->logger->critical(
                sprintf(
                    'Transaction amount %s does not match to payment amount %s. Order: %s.',
                    $transactionAmount,
                    $paymentTransaction->getAmount(),
                    $paymentTransaction->getEntityIdentifier()
                )
            );

            throw new \UnexpectedValueException('Transaction amount doesn\'t match to order amount.');
        }
    }

    /**
     * Get Initial Payment Transaction.
     *
     * @param Order $order
     * @return PaymentTransaction|null
     */
    private function getInitialTransaction(Order $order): ?PaymentTransaction
    {
        foreach (
            [
                PaymentMethodInterface::PURCHASE,
                PaymentMethodInterface::AUTHORIZE,
                PaymentMethodInterface::CAPTURE
            ] as $action
        ) {
            $transactions = $this->paymentTransactionProvider->getPaymentTransactions(
                $order,
                [
                    'action' => $action,
                    'amount' => (string)round((float) $order->getTotal(), 2)
                ]
            );
            if (count($transactions) > 0) {
                return array_shift($transactions);
            }
        }

        return null;
    }

    /**
     * Get Payment Transaction by Order Reference.
     *
     * @param string $orderReference
     * @return PaymentTransaction|null
     */
    private function getPaymentTransactionByOrderReference(string $orderReference): ?PaymentTransaction
    {
        $order = $this->transactionHelper->getOrderByIdentifier($orderReference);
        if ($order) {
            return $this->paymentTransactionProvider->getPaymentTransaction($order, [], ['id' => Criteria::ASC]);
        }

        return null;
    }
}
