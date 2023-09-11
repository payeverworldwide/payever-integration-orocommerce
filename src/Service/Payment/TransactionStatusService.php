<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Payever\Bundle\PaymentBundle\Service\Helper\CompareHelper;
use Payever\Bundle\PaymentBundle\Service\Helper\TransactionHelper;
use Payever\Bundle\PaymentBundle\Service\Management\OrderManager;
use Payever\Sdk\Payments\Enum\Status;
use Payever\Sdk\Payments\Http\MessageEntity\RetrievePaymentResultEntity;
use Psr\Log\LoggerInterface;

class TransactionStatusService
{
    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;

    /**
     * @var PaymentTransactionRepository
     */
    private PaymentTransactionRepository $paymentTransactionRepository;

    private TransactionHelper $transactionHelper;

    /**
     * @var OrderManager
     */
    private OrderManager $orderManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct(
        EntityManager $entityManager,
        PaymentTransactionRepository $paymentTransactionRepository,
        TransactionHelper $transactionHelper,
        OrderManager $orderManager,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->paymentTransactionRepository = $paymentTransactionRepository;
        $this->transactionHelper = $transactionHelper;
        $this->orderManager = $orderManager;
        $this->logger = $logger;
    }

    public function persistTransactionStatus(RetrievePaymentResultEntity $payeverPayment)
    {
        $paymentId = $payeverPayment->getId();
        $orderId = $payeverPayment->getReference();

        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $this->getPaymentTransaction($orderId);
        if (!$paymentTransaction) {
            throw new \UnexpectedValueException('Payment transaction is missing for Order #' . $orderId);
        }

        $order = $this->transactionHelper->getOrder($paymentTransaction);
        if (!$order) {
            throw new \UnexpectedValueException('Order is not found');
        }

        // Verify transaction amount
        if (!CompareHelper::areSame((float) $payeverPayment->getTotal(), (float) $paymentTransaction->getAmount())) {
            // @todo Trigger payment cancel/refund
            $this->logger->critical(
                sprintf(
                    'Transaction amount %s does not match to payment amount %s. Order: %s.',
                    $payeverPayment->getTotal(),
                    $paymentTransaction->getAmount(),
                    $orderId
                )
            );

            throw new \UnexpectedValueException('Transaction amount doesn\'t match to order amount.');
        }

        $paymentTransaction->setReference($paymentId);

        // Save payment details
        $this->logger->debug(json_encode($payeverPayment->toArray()));
        $this->transactionHelper->updateTransactionOptions($paymentTransaction, $payeverPayment->toArray());

        // Allocate order items
        $this->orderManager->allocateOrderItems($order);

        switch ($payeverPayment->getStatus()) {
            case Status::STATUS_IN_PROCESS:
            case Status::STATUS_ACCEPTED:
                $paymentTransaction->setAction(PaymentMethodInterface::AUTHORIZE)
                    ->setActive(true);
                break;
            case Status::STATUS_PAID:
                $paymentTransaction->setAction(PaymentMethodInterface::CAPTURE)
                    ->setActive(false);

                // Mark order items captured
                $this->orderManager->shipOrderItems(
                    $order,
                    $this->orderManager->getOrderQtyAvailableForCapture($order)
                );

                break;
            default:
                $paymentTransaction->setAction(PaymentMethodInterface::CANCEL);
                break;
        }

        $paymentTransaction
            ->setSuccessful(true);
    }

    /**
     * Checks if Notification will be rejected.
     *
     * @param string $reference
     * @param int $notificationTimestamp
     * @return bool
     */
    public function shouldRejectNotification(
        $reference,
        int $notificationTimestamp
    ): bool {
        $paymentTransaction = $this->getPaymentTransaction($reference);
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
        $paymentTransaction = $this->getPaymentTransaction($reference);
        if (!$paymentTransaction) {
            throw new \UnexpectedValueException('Payment transaction is missing for Order #' . $reference);
        }

        $transactionOptions = $paymentTransaction->getTransactionOptions();
        $transactionOptions['notificationTimestamp'] = $notificationTimestamp;

        $paymentTransaction->setTransactionOptions($transactionOptions);

        $this->entityManager->persist($paymentTransaction);
        $this->entityManager->flush($paymentTransaction);
    }

    /**
     * @todo Filter by purchase type.
     * @param $orderId
     *
     * @return PaymentTransaction|null
     */
    private function getPaymentTransaction($orderId): ?PaymentTransaction
    {
        /** @var PaymentTransaction $paymentTransaction */
        return $this->paymentTransactionRepository->findOneBy(
            [
                'entityClass' => Order::class,
                'entityIdentifier' => $orderId
            ]
        );
    }
}
