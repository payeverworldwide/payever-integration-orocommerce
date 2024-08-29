<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Payever\Bundle\PaymentBundle\Service\Helper\CompareHelper;
use Payever\Bundle\PaymentBundle\Service\Helper\TransactionHelper;
use Payever\Bundle\PaymentBundle\Service\Management\OrderManager;
use Payever\Sdk\Payments\Action\ActionDeciderInterface;
use Psr\Log\LoggerInterface;

class TransactionBuilderService
{
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

    public function registerCaptureTransaction(
        Order $order,
        string $paymentId,
        float $captureAmount,
        array $response
    ): PaymentTransaction {
        // Get source transaction
        $sourceTransaction = $this->transactionHelper->getPaymentTransaction(
            $order,
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
    public function registerRefundTransaction(
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
                    $order,
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

    public function registerCancelTransaction(
        Order $order,
        string $paymentId,
        float $captureAmount,
        array $response
    ): PaymentTransaction {
        // Get source transaction
        $sourceTransaction = $this->transactionHelper->getPaymentTransaction(
            $order,
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

    public function registerSettleTransaction(
        Order $order,
        string $paymentId,
        float $settleAmount,
        array $response
    ): PaymentTransaction {
        // Get source transaction
        $sourceTransaction = $this->transactionHelper->getPaymentTransaction(
            $order,
            PaymentMethodInterface::AUTHORIZE
        );

        if (!$sourceTransaction) {
            throw new \LogicException('Impossible to get source transaction.');
        }

        // Create transaction
        $paymentTransaction = $this->paymentTransactionProvider->createPaymentTransactionByParentTransaction(
            ActionDeciderInterface::ACTION_SETTLE,
            $sourceTransaction
        );

        $paymentTransaction
            ->setAmount($settleAmount)
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

    public function registerClaimTransaction(
        Order $order,
        string $paymentId,
        array $response
    ): PaymentTransaction {
        // Get source transaction
        $sourceTransaction = $this->transactionHelper->getPaymentTransaction(
            $order,
            PaymentMethodInterface::CAPTURE
        );

        if (!$sourceTransaction) {
            throw new \LogicException('Impossible to get source transaction.');
        }

        // Create transaction
        $paymentTransaction = $this->paymentTransactionProvider->createPaymentTransactionByParentTransaction(
            ActionDeciderInterface::ACTION_CLAIM,
            $sourceTransaction
        );

        $paymentTransaction
            ->setReference($paymentId)
            ->setTransactionOptions($sourceTransaction->getTransactionOptions())
            ->setSuccessful(true)
            ->setActive(false)
            ->setResponse($response);
        $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);

        return $paymentTransaction;
    }

    public function registerClaimUploadTransaction(
        Order $order,
        string $paymentId,
        array $response
    ): PaymentTransaction {
        // Get source transaction
        $sourceTransaction = $this->transactionHelper->getPaymentTransaction(
            $order,
            PaymentMethodInterface::CAPTURE
        );

        if (!$sourceTransaction) {
            throw new \LogicException('Impossible to get source transaction.');
        }

        // Create transaction
        $paymentTransaction = $this->paymentTransactionProvider->createPaymentTransactionByParentTransaction(
            ActionDeciderInterface::ACTION_CLAIM_UPLOAD,
            $sourceTransaction
        );

        $paymentTransaction
            ->setReference($paymentId)
            ->setTransactionOptions($sourceTransaction->getTransactionOptions())
            ->setSuccessful(true)
            ->setActive(false)
            ->setResponse($response);
        $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);

        return $paymentTransaction;
    }

    public function registerInvoiceTransaction(
        Order $order,
        string $paymentId,
        float $invoiceAmount,
        array $response
    ): PaymentTransaction {
        // Get source transaction
        $sourceTransaction = $this->transactionHelper->getPaymentTransaction(
            $order,
            PaymentMethodInterface::CAPTURE
        );

        if (!$sourceTransaction) {
            throw new \LogicException('Impossible to get source transaction.');
        }

        // Create transaction
        $paymentTransaction = $this->paymentTransactionProvider->createPaymentTransactionByParentTransaction(
            ActionDeciderInterface::ACTION_INVOICE,
            $sourceTransaction
        );

        $paymentTransaction
            ->setAmount($invoiceAmount)
            ->setReference($paymentId)
            ->setTransactionOptions($sourceTransaction->getTransactionOptions())
            ->setSuccessful(true)
            ->setActive(false)
            ->setResponse($response);
        $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);

        return $paymentTransaction;
    }
}
