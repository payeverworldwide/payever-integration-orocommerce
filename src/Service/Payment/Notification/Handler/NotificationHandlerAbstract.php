<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Notification\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Payever\Bundle\PaymentBundle\Service\Helper\CompareHelper;
use Payever\Bundle\PaymentBundle\Service\Helper\TransactionHelper;
use Payever\Bundle\PaymentBundle\Service\Management\OrderManager;
use Payever\Bundle\PaymentBundle\Service\Payment\TransactionStatusService;
use Psr\Log\LoggerInterface;

abstract class NotificationHandlerAbstract
{
    protected const ITEM_QTY = 'quantity';
    protected const ITEM_PRICE = 'price';

    protected EntityManager $entityManager;
    protected TransactionStatusService $transactionStatusService;
    protected OrderManager $orderManager;
    protected TransactionHelper $transactionHelper;
    protected PaymentTransactionProvider $paymentTransactionProvider;
    protected LoggerInterface $logger;

    public function __construct(
        EntityManager $entityManager,
        TransactionStatusService $transactionStatusService,
        OrderManager $orderManager,
        TransactionHelper $transactionHelper,
        PaymentTransactionProvider $paymentTransactionProvider,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->transactionStatusService = $transactionStatusService;
        $this->orderManager = $orderManager;
        $this->transactionHelper = $transactionHelper;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
        $this->logger = $logger;
    }

    protected function isPaymentTransactionExists(Order $order, string $action, float $amount): bool
    {
        $paymentTransaction = $this->paymentTransactionProvider->getPaymentTransaction(
            $order,
            [
                'action' => $action,
                'amount' => $amount,
            ]
        );

        return !is_null($paymentTransaction);
    }

    protected function registerCaptureTransaction(
        Order $order,
        string $paymentId,
        float $captureAmount,
        array $response
    ): PaymentTransaction {
        // Get source transaction
        $sourceTransaction = $this->transactionHelper->getPaymentTransaction(
            $order->getId(),
            PaymentMethodInterface::AUTHORIZE
        );

        if (!$sourceTransaction) {
            throw new \LogicException('Impossible to get source transaction.');
        }

        // Create transaction
        $paymentTransaction = $this->paymentTransactionProvider->createPaymentTransactionByParentTransaction(
            PaymentMethodInterface::CAPTURE,
            $sourceTransaction
        );

        $paymentTransaction->setAmount($captureAmount)
            ->setReference($paymentId)
            ->setTransactionOptions($sourceTransaction->getTransactionOptions())
            ->setSuccessful(true)
            ->setActive(false)
            ->setResponse($response);
        $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);

        $sourceTransaction->setActive(true);
        $this->paymentTransactionProvider->savePaymentTransaction($sourceTransaction);

        return $paymentTransaction;
    }

    /**
     * @param Order $order
     * @param string $paymentId
     * @param float $refundAmount
     * @param array $response
     * @return PaymentTransaction
     * @throws \Throwable
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function registerRefundTransaction(
        Order $order,
        string $paymentId,
        float $refundAmount,
        array $response
    ): PaymentTransaction {
        // Get source transaction
        $sourceTransaction = null;
        $paymentTransactions = $this->paymentTransactionProvider->getPaymentTransactions(
            $order,
            [
                'action' => PaymentMethodInterface::CAPTURE
            ]
        );
        foreach ($paymentTransactions as $paymentTransaction) {
            if (CompareHelper::areSame((float) $paymentTransaction->getAmount(), (float) $refundAmount)) {
                $sourceTransaction = $paymentTransaction;
                break;
            }
        }

        if (!$sourceTransaction) {
            if (count($paymentTransactions) > 0) {
                $sourceTransaction = array_shift($paymentTransactions);
            } else {
                $sourceTransaction = $this->transactionHelper->getPaymentTransaction(
                    $order->getId(),
                    PaymentMethodInterface::AUTHORIZE
                );
            }
        }

        if (!$sourceTransaction) {
            throw new \LogicException('Impossible to get source transaction.');
        }

        // Create transaction
        $paymentTransaction = $this->paymentTransactionProvider->createPaymentTransactionByParentTransaction(
            PaymentMethodInterface::REFUND,
            $sourceTransaction
        );

        $paymentTransaction->setAmount($refundAmount)
            ->setReference($paymentId)
            ->setTransactionOptions($sourceTransaction->getTransactionOptions())
            ->setSuccessful(true)
            ->setActive(true)
            ->setResponse($response);
        $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);

        return $paymentTransaction;
    }

    protected function registerCancelTransaction(
        Order $order,
        string $paymentId,
        float $captureAmount,
        array $response
    ): PaymentTransaction {
        // Get source transaction
        $sourceTransaction = $this->transactionHelper->getPaymentTransaction(
            $order->getId(),
            PaymentMethodInterface::AUTHORIZE
        );

        if (!$sourceTransaction) {
            throw new \LogicException('Impossible to get source transaction.');
        }

        // Create transaction
        $paymentTransaction = $this->paymentTransactionProvider->createPaymentTransactionByParentTransaction(
            PaymentMethodInterface::CANCEL,
            $sourceTransaction
        );

        $paymentTransaction->setAmount($captureAmount)
            ->setReference($paymentId)
            ->setTransactionOptions($sourceTransaction->getTransactionOptions())
            ->setSuccessful(true)
            ->setActive(false)
            ->setResponse($response);
        $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);

        $sourceTransaction->setActive(false);
        $this->paymentTransactionProvider->savePaymentTransaction($sourceTransaction);

        return $paymentTransaction;
    }
}
