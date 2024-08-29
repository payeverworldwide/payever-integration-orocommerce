<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\PaymentAction;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfig;
use Payever\Bundle\PaymentBundle\Service\Payment\Action\ShippingAction;
use Payever\Bundle\PaymentBundle\Service\Helper\TransactionHelper;
use Payever\Sdk\Payments\Http\ResponseEntity\ShippingGoodsPaymentResponse;
use Payever\Sdk\Payments\Notification\NotificationRequestProcessor;
use Payever\Sdk\Core\Lock\LockInterface;
use Psr\Log\LoggerInterface;

class CapturePaymentAction implements PaymentActionInterface
{
    private TransactionHelper $transactionHelper;
    private ShippingAction $shippingAction;
    private PaymentTransactionProvider $paymentTransactionProvider;
    private LoggerInterface $logger;
    private LockInterface $lock;

    public function __construct(
        TransactionHelper $transactionHelper,
        ShippingAction $shippingAction,
        PaymentTransactionProvider $paymentTransactionProvider,
        LoggerInterface $logger,
        LockInterface $lock
    ) {
        $this->transactionHelper = $transactionHelper;
        $this->shippingAction = $shippingAction;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
        $this->logger = $logger;
        $this->lock = $lock;
    }

    public function execute(
        PayeverConfig $config,
        PaymentTransaction $paymentTransaction
    ): array {
        $this->logger->debug('Capture action');
        $sourceTransaction = $paymentTransaction->getSourcePaymentTransaction();
        if (!$sourceTransaction) {
            throw new \LogicException('Payment could not be captured. Authorize transaction not found');
        }

        $paymentTransaction->setActive(false);
        $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);

        $order = $this->transactionHelper->getOrder($paymentTransaction);
        if (!$order) {
            throw new \UnexpectedValueException('Order is not found');
        }

        $paymentId = $this->transactionHelper->getPaymentId($sourceTransaction);
        if (!$paymentId) {
            throw new \LogicException('Payment transaction does not have stored payment id.');
        }

        $captureAmount = $this->transactionHelper->getTransactionOption($paymentTransaction, 'captureAmount');
        if ($captureAmount) {
            $this->logger->debug('Capture amount: ' . $captureAmount);
        }

        $this->lock->acquireLock($paymentId, NotificationRequestProcessor::NOTIFICATION_LOCK_SECONDS);

        try {
            /** @var ShippingGoodsPaymentResponse $response */
            $response = $this->shippingAction->execute($order, $captureAmount);

            $paymentTransaction->setReference($paymentId)
                ->setSuccessful(true)
                ->setResponse($response->getResult()->toArray());

            if ($captureAmount) {
                $paymentTransaction->setAmount($captureAmount);
            }

            $sourceTransaction->setActive(true);
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
        return $action === PaymentMethodInterface::CAPTURE;
    }
}
