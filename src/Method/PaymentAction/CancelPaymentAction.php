<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\PaymentAction;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfig;
use Payever\Bundle\PaymentBundle\Service\Payment\Action\CancelAction;
use Payever\Bundle\PaymentBundle\Service\Helper\TransactionHelper;
use Payever\Sdk\Core\Lock\LockInterface;
use Payever\Sdk\Payments\Http\ResponseEntity\CancelPaymentResponse;
use Payever\Sdk\Payments\Notification\NotificationRequestProcessor;
use Psr\Log\LoggerInterface;

class CancelPaymentAction implements PaymentActionInterface
{
    private TransactionHelper $transactionHelper;
    private CancelAction $cancelAction;
    private PaymentTransactionProvider $paymentTransactionProvider;
    private LoggerInterface $logger;
    private LockInterface $lock;

    public function __construct(
        TransactionHelper $transactionHelper,
        CancelAction $cancelAction,
        PaymentTransactionProvider $paymentTransactionProvider,
        LoggerInterface $logger,
        LockInterface $lock
    ) {
        $this->transactionHelper = $transactionHelper;
        $this->cancelAction = $cancelAction;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
        $this->logger = $logger;
        $this->lock = $lock;
    }

    public function execute(
        PayeverConfig $config,
        PaymentTransaction $paymentTransaction
    ): array {
        $this->logger->debug('Cancel action');

        $sourceTransaction = $paymentTransaction->getSourcePaymentTransaction();
        if (!$sourceTransaction) {
            throw new \LogicException('Payment could not be canceled. Authorize transaction not found');
        }

        if ($sourceTransaction->getAction() !== PaymentMethodInterface::AUTHORIZE) {
            throw new \LogicException('Payment could not be canceled. Transaction should be authorized first');
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

        $cancelAmount = $this->transactionHelper->getTransactionOption($paymentTransaction, 'cancelAmount');
        if ($cancelAmount) {
            $this->logger->debug('Cancel amount: ' . $cancelAmount);
        }

        $this->lock->acquireLock($paymentId, NotificationRequestProcessor::NOTIFICATION_LOCK_SECONDS);

        try {
            /** @var CancelPaymentResponse $response */
            $response = $this->cancelAction->execute($order, $cancelAmount);

            $paymentTransaction->setReference($paymentId)
                ->setSuccessful(true)
                ->setResponse($response->getResult()->toArray());

            if ($cancelAmount) {
                $paymentTransaction->setAmount($cancelAmount);
            }
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
        return $action === PaymentMethodInterface::CANCEL;
    }
}
