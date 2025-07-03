<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Helper;

use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

class OrderHelper
{
    private Registry $doctrine;
    private TransactionHelper $transactionHelper;
    private PaymentTransactionProvider $paymentTransactionProvider;

    public function __construct(
        Registry $doctrine,
        TransactionHelper $transactionHelper,
        PaymentTransactionProvider $paymentTransactionProvider
    ) {
        $this->doctrine = $doctrine;
        $this->transactionHelper = $transactionHelper;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
    }

    /**
     * Get Order by ID.
     *
     * @param int $orderId
     *
     * @return Order|null
     */
    public function getOrderByID(int $orderId): ?Order
    {
        return $this->getOrderRepository()->findOneBy(
            ['id' => $orderId]
        );
    }

    /**
     * Get Payment ID.
     *
     * @param Order $order
     *
     * @return string|null
     * @throws \Exception
     */
    public function getPaymentId(Order $order): ?string
    {
        $paymentId = null;
        $paymentTransactions = $this->paymentTransactionProvider->getPaymentTransactions($order);
        foreach ($paymentTransactions as $paymentTransaction) {
            $paymentId = $this->transactionHelper->getPaymentId($paymentTransaction);
            if ($paymentId) {
                break;
            }
        }

        return $paymentId;
    }

    /**
     * Get Next Autoincrement ID.
     *
     * @return string
     */
    public function getReservedOrderIdentifier(): string
    {
        /** @var Order $order */
        $order = $this->transactionHelper->getOrderRepository()->findOneBy([], ['id' => 'DESC']);
        if (!$order) {
            return '0';
        }

        return (string) ($order->getId() + 1);
    }

    private function getOrderRepository(): ?OrderRepository
    {
        return $this->doctrine
            ->getManagerForClass(Order::class)
            ->getRepository(Order::class);
    }
}
