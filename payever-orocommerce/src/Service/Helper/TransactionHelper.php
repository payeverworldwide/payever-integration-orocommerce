<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Helper;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

class TransactionHelper
{
    private const FIELD_ID = 'id';

    private Registry $doctrine;
    private DoctrineHelper $doctrineHelper;
    private PaymentTransactionProvider $paymentTransactionProvider;

    public function __construct(
        Registry $doctrine,
        DoctrineHelper $doctrineHelper,
        PaymentTransactionProvider $paymentTransactionProvider
    ) {
        $this->doctrine = $doctrine;
        $this->doctrineHelper = $doctrineHelper;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
    }

    public function getOrderByID($orderId): ?Order
    {
        return $this->getOrderRepository()->findOneBy(
            ['id' => intval($orderId)]
        );
    }

    public function getPaymentTransaction(int $orderId, string $action): PaymentTransaction
    {
        $order = $this->getOrderByID($orderId);
        if (!$order) {
            throw new \InvalidArgumentException('Order is not found.');
        }

        $paymentTransaction = $this->paymentTransactionProvider->getPaymentTransaction(
            $order,
            [
                'action' => $action
            ]
        );

        if (!$paymentTransaction) {
            throw new \InvalidArgumentException('Payment transaction is missing.');
        }

        return $paymentTransaction;
    }

    /**
     * Get Order entity.
     *
     * @param PaymentTransaction $paymentTransaction
     *
     * @return Order|null
     */
    public function getOrder(PaymentTransaction $paymentTransaction): ?Order
    {
        /** @var Order $entity */
        return $this->doctrineHelper->getEntityReference(
            $paymentTransaction->getEntityClass(),
            $paymentTransaction->getEntityIdentifier()
        );
    }

    /**
     * Get Transaction Additional Data.
     *
     * @param PaymentTransaction $paymentTransaction
     *
     * @return array
     */
    public function getTransactionAdditionalData(PaymentTransaction $paymentTransaction): array
    {
        $transactionOptions = $paymentTransaction->getTransactionOptions();

        return isset($transactionOptions['additionalData'])
            ? json_decode($transactionOptions['additionalData'], true)
            : [];
    }

    /**
     * Get Transaction Option.
     *
     * @param PaymentTransaction $paymentTransaction
     * @param string $optionName
     * @return mixed|null
     */
    public function getTransactionOption(PaymentTransaction $paymentTransaction, string $optionName)
    {
        return $paymentTransaction->getTransactionOptions()[$optionName] ?? null;
    }

    /**
     * Get Payment ID.
     *
     * @param PaymentTransaction $paymentTransaction
     *
     * @return string
     * @throws \Exception
     */
    public function getPaymentId(PaymentTransaction $paymentTransaction): string
    {
        $additionalData = $this->getTransactionAdditionalData($paymentTransaction);
        if (isset($additionalData[self::FIELD_ID])) {
            return $additionalData[self::FIELD_ID];
        }

        throw new \Exception('Payment transaction does not have stored payment id.');
    }

    /**
     * Update `additionalData` of payment transaction.
     *
     * @param PaymentTransaction $paymentTransaction
     * @param array $data
     *
     * @return void
     */
    public function updateTransactionOptions(PaymentTransaction $paymentTransaction, array $data): void
    {
        $transactionOptions = $paymentTransaction->getTransactionOptions();

        $additionalOptions = (array) json_decode($transactionOptions['additionalData'] ?? '[]', true);
        $additionalOptions = array_merge($additionalOptions, $data);
        $transactionOptions['additionalData'] = json_encode($additionalOptions);
        $paymentTransaction->setTransactionOptions($transactionOptions);
    }

    private function getOrderRepository(): ?OrderRepository
    {
        return $this->doctrine
            ->getManagerForClass(Order::class)
            ->getRepository(Order::class);
    }
}
