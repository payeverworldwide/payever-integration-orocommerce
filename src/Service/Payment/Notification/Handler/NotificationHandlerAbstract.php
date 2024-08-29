<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Notification\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Payever\Bundle\PaymentBundle\Service\Helper\OrderHelper;
use Payever\Bundle\PaymentBundle\Service\Helper\TransactionHelper;
use Payever\Bundle\PaymentBundle\Service\Management\OrderManager;
use Payever\Bundle\PaymentBundle\Service\Payment\TransactionBuilderService;
use Psr\Log\LoggerInterface;

abstract class NotificationHandlerAbstract
{
    protected const ITEM_QTY = 'quantity';
    protected const ITEM_PRICE = 'price';

    protected EntityManager $entityManager;
    protected TransactionBuilderService $transactionBuilder;
    protected OrderManager $orderManager;
    protected OrderHelper $orderHelper;
    protected TransactionHelper $transactionHelper;
    protected PaymentTransactionProvider $paymentTransactionProvider;
    protected LoggerInterface $logger;

    public function __construct(
        EntityManager $entityManager,
        PaymentTransactionProvider $paymentTransactionProvider,
        OrderHelper $orderHelper,
        TransactionBuilderService $transactionBuilder,
        OrderManager $orderManager,
        TransactionHelper $transactionHelper,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
        $this->orderHelper = $orderHelper;
        $this->transactionBuilder = $transactionBuilder;
        $this->orderManager = $orderManager;
        $this->transactionHelper = $transactionHelper;
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
}
