<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\PaymentAction;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfig;
use Payever\Bundle\PaymentBundle\Service\Payment\Action\RefundAction;
use Payever\Bundle\PaymentBundle\Service\Helper\TransactionHelper;
use Payever\Sdk\Core\Lock\LockInterface;
use Payever\Sdk\Payments\Http\ResponseEntity\RefundPaymentResponse;
use Payever\Sdk\Payments\Notification\NotificationRequestProcessor;
use Psr\Log\LoggerInterface;

class RefundPaymentAction implements PaymentActionInterface
{
    private TransactionHelper $transactionHelper;
    private RefundAction $refundAction;
    private PaymentTransactionProvider $paymentTransactionProvider;
    private LoggerInterface $logger;
    private LockInterface $lock;

    public function __construct(
        TransactionHelper $transactionHelper,
        RefundAction $refundAction,
        PaymentTransactionProvider $paymentTransactionProvider,
        LoggerInterface $logger,
        LockInterface $lock
    ) {
        $this->transactionHelper = $transactionHelper;
        $this->refundAction = $refundAction;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
        $this->logger = $logger;
        $this->lock = $lock;
    }

    public function execute(
        PayeverConfig $config,
        PaymentTransaction $paymentTransaction
    ): array {
        $sourceTransaction = $paymentTransaction->getSourcePaymentTransaction();
        if (!$sourceTransaction) {
            throw new \LogicException('Payment could not be refunded. Capture transaction not found');
        }

        $paymentTransaction->setActive(true);
        $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);

        $order = $this->transactionHelper->getOrder($paymentTransaction);
        if (!$order) {
            throw new \UnexpectedValueException('Order is not found');
        }

        $paymentId = $this->transactionHelper->getPaymentId($sourceTransaction);
        if (!$paymentId) {
            throw new \LogicException('Payment transaction does not have stored payment id.');
        }

        $refundAmount = $this->transactionHelper->getTransactionOption($paymentTransaction, 'refundAmount');
        if ($refundAmount) {
            $this->logger->debug('Refund amount: ' . $refundAmount);
        }

        $this->lock->acquireLock($paymentId, NotificationRequestProcessor::NOTIFICATION_LOCK_SECONDS);

        try {
            /** @var RefundPaymentResponse $response */
            $response = $this->refundAction->execute($order, $refundAmount);

            $paymentTransaction->setReference($paymentId)
                ->setSuccessful(true)
                ->setResponse($response->getResult()->toArray());

            if ($refundAmount) {
                $paymentTransaction->setAmount($refundAmount);
            }

            $paymentTransaction->setActive(false);
            $paymentTransaction->setTransactionOptions($sourceTransaction->getTransactionOptions());
            $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);
        } catch (\Exception $exception) {
            $this->lock->releaseLock($paymentId);

            return [
                'successful' => false,
                'error' => $exception->getMessage()
            ];
        }

        $this->lock->releaseLock($paymentId);

        return [
            'successful' => true,
        ];
    }

    public function isApplicable(string $action, PaymentTransaction $paymentTransaction): bool
    {
        return $action === PaymentMethodInterface::REFUND;
    }
}
