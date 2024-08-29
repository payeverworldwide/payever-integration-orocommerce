<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Management;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Payever\Bundle\PaymentBundle\Service\Factory\OrderTotalFactory;
use Payever\Bundle\PaymentBundle\Service\Factory\OrderItemFactory;
use Payever\Bundle\PaymentBundle\Entity\OrderItems;
use Payever\Bundle\PaymentBundle\Entity\OrderTotals;
use Payever\Bundle\PaymentBundle\Entity\Repository\OrderTotalsRepository;
use Payever\Bundle\PaymentBundle\Entity\Repository\OrderItemsRepository;
use Payever\Bundle\PaymentBundle\Service\Helper\OrderItemHelper;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OrderManager
{
    /**
     * @var Registry
     */
    private Registry $doctrine;

    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;

    /**
     * @var OrderTotalFactory
     */
    private OrderTotalFactory $orderTotalFactory;

    /**
     * @var OrderItemFactory
     */
    private OrderItemFactory $orderItemFactory;

    /**
     * @var OrderItemHelper
     */
    private OrderItemHelper $orderItemHelper;

    public function __construct(
        Registry $doctrine,
        EntityManager $entityManager,
        OrderTotalFactory $orderTotalFactory,
        OrderItemFactory $orderItemFactory,
        OrderItemHelper $orderItemHelper
    ) {
        $this->doctrine = $doctrine;
        $this->entityManager = $entityManager;
        $this->orderTotalFactory = $orderTotalFactory;
        $this->orderItemFactory = $orderItemFactory;
        $this->orderItemHelper = $orderItemHelper;
    }

    /**
     * Allocate Order items and totals.
     *
     * @param Order $order
     *
     * @return void
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function allocateOrderItems(Order $order): void
    {
        // Check if order items were allocated already
        $items = $this->getOrderItemsRepository()->findByOrder($order);
        if (count($items) > 0) {
            return;
        }

        $items = $this->orderItemHelper->getOrderItems($order);
        foreach ($items as $item) {
            $orderItem = $this->orderItemFactory->create();
            $orderItem->setOrderId($order->getId())
                ->setName($item[OrderItemHelper::PROP_NAME])
                ->setItemType($item[OrderItemHelper::PROP_TYPE])
                ->setItemReference($item[OrderItemHelper::PROP_SKU])
                ->setQuantity($item[OrderItemHelper::PROP_QUANTITY])
                ->setUnitPrice($item[OrderItemHelper::PROP_UNIT_PRICE_INCL_TAX])
                ->setTotalPrice($item[OrderItemHelper::PROP_TOTAL_PRICE_INCL_TAX])
                ->setQtyCaptured(0)
                ->setQtyRefunded(0)
                ->setQtyCancelled(0);

            $this->entityManager->persist($orderItem);
            $this->entityManager->flush($orderItem);
        }

        $orderTotal = $this->orderTotalFactory->create();
        $orderTotal->setOrderId($order->getId())
            ->setCapturedTotal(0)
            ->setCancelledTotal(0)
            ->setRefundedTotal(0)
            ->setSettledTotal(0)
            ->setInvoicedTotal(0)
            ->setManual(false);

        $this->entityManager->persist($orderTotal);
        $this->entityManager->flush($orderTotal);
    }

    /**
     * @param Order $order
     *
     * @return OrderItems[]
     */
    public function getOrderItems(Order $order): array
    {
        return $this->getOrderItemsRepository()->findByOrder($order);
    }

    /**
     * Get Order Item.
     *
     * @param Order $order
     * @param string $itemReference
     *
     * @return OrderItems|null
     */
    public function getOrderItem(Order $order, string $itemReference): ?OrderItems
    {
        return $this->getOrderItemsRepository()
            ->findByItemReference($order, $itemReference);
    }

    public function getOrderItemByType(Order $order, string $type): ?OrderItems
    {
        return $this->getOrderItemsRepository()
            ->findByItemReference($order, $type);
    }

    public function getOrderTotal(Order $order): ?OrderTotals
    {
        return $this->getOrderTotalsRepository()->findByOrder($order);
    }

    public function getOrderQty(Order $order): array
    {
        $result = [];
        $items = $this->getOrderItems($order);
        foreach ($items as $item) {
            $result[$item->getId()] = $item->getQuantity();
        }

        return $result;
    }

    public function getOrderQtyAvailableForCancel(Order $order): array
    {
        $result = [];
        $items = $this->getOrderItems($order);
        foreach ($items as $item) {
            $result[$item->getItemReference()] = $item->getCanBeCancelled();
        }

        return $result;
    }

    public function getOrderQtyAvailableForCapture(Order $order): array
    {
        $result = [];
        $items = $this->getOrderItems($order);
        foreach ($items as $item) {
            $result[$item->getItemReference()] = $item->getCanBeCaptured();
        }

        return $result;
    }

    public function getOrderQtyAvailableForRefund(Order $order): array
    {
        $result = [];
        $items = $this->getOrderItems($order);
        foreach ($items as $item) {
            $result[$item->getItemReference()] = $item->getCanBeRefunded();
        }

        return $result;
    }

    /**
     * Add cancelled amount.
     * @param Order $order
     * @param float $amount
     * @param bool $isManual
     *
     * @return void
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addCancelledAmount(Order $order, float $amount, bool $isManual)
    {
        $orderTotal = $this->getOrderTotalsRepository()->findByOrder($order);
        if (!$orderTotal) {
            throw new \Exception('Order totals is not found.');
        }

        $orderTotal->setCancelledTotal($orderTotal->getCancelledTotal() + $amount);
        if ($isManual) {
            $orderTotal->setManual(true);
        }

        $this->entityManager->persist($orderTotal);
        $this->entityManager->flush($orderTotal);
    }

    /**
     * Add captured amount.
     * @param Order $order
     * @param float $amount
     * @param bool $isManual
     *
     * @return void
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addCapturedAmount(Order $order, float $amount, bool $isManual)
    {
        $orderTotal = $this->getOrderTotalsRepository()->findByOrder($order);
        if (!$orderTotal) {
            throw new \Exception('Order totals is not found.');
        }

        $orderTotal->setCapturedTotal($orderTotal->getCapturedTotal() + $amount);
        if ($isManual) {
            $orderTotal->setManual(true);
        }

        $this->entityManager->persist($orderTotal);
        $this->entityManager->flush($orderTotal);
    }

    /**
     * Add refunded amount.
     * @param Order $order
     * @param float $amount
     * @param bool $isManual
     *
     * @return void
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addRefundedAmount(Order $order, float $amount, bool $isManual)
    {
        $orderTotal = $this->getOrderTotalsRepository()->findByOrder($order);
        if (!$orderTotal) {
            throw new \Exception('Order totals is not found.');
        }

        $orderTotal->setRefundedTotal($orderTotal->getRefundedTotal() + $amount);
        if ($isManual) {
            $orderTotal->setManual(true);
        }

        $this->entityManager->persist($orderTotal);
        $this->entityManager->flush($orderTotal);
    }

    /**
     * Add settled amount.
     * @param Order $order
     * @param float $amount
     * @param bool $isManual
     *
     * @return void
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addSettledAmount(Order $order, float $amount, bool $isManual)
    {
        $orderTotal = $this->getOrderTotalsRepository()->findByOrder($order);
        if (!$orderTotal) {
            throw new \Exception('Order totals is not found.');
        }

        $orderTotal->setSettledTotal($orderTotal->getSettledTotal() + $amount);
        if ($isManual) {
            $orderTotal->setManual(true);
        }

        $this->entityManager->persist($orderTotal);
        $this->entityManager->flush($orderTotal);
    }

    /**
     * Mark Order items cancelled.
     *
     * @param Order $order
     * @param array $items
     * @return void
     */
    public function cancelOrderItems(Order $order, array $items): void
    {
        $totalAmount = 0;
        foreach ($items as $itemReference => $qty) {
            // Update items
            $orderItem = $this->getOrderItemsRepository()
                ->findByItemReference($order, $itemReference);
            if (!$orderItem) {
                continue;
            }

            $orderItem->setQtyCancelled($orderItem->getQtyCancelled() + $qty);
            $this->entityManager->persist($orderItem);
            $this->entityManager->flush($orderItem);

            $totalAmount += ($orderItem->getUnitPrice() * $qty);
        }

        // Update totals
        $this->addCancelledAmount($order, $totalAmount, false);
    }

    /**
     * Mark Order items refunded.
     *
     * @param Order $order
     * @param array $items
     * @return void
     */
    public function refundOrderItems(Order $order, array $items): void
    {
        $totalAmount = 0;
        foreach ($items as $itemReference => $qty) {
            // Update items
            $orderItem = $this->getOrderItemsRepository()
                ->findByItemReference($order, $itemReference);
            if (!$orderItem) {
                continue;
            }

            $orderItem->setQtyRefunded($orderItem->getQtyRefunded() + $qty);
            $this->entityManager->persist($orderItem);
            $this->entityManager->flush($orderItem);

            $totalAmount += ($orderItem->getUnitPrice() * $qty);
        }

        // Update totals
        $this->addRefundedAmount($order, $totalAmount, false);
    }

    /**
     * Mark Order items shipped.
     *
     * @param Order $order
     * @param array $items
     * @return void
     */
    public function shipOrderItems(Order $order, array $items): void
    {
        $totalAmount = 0;
        foreach ($items as $itemReference => $qty) {
            // Update items
            $orderItem = $this->getOrderItemsRepository()
                ->findByItemReference($order, $itemReference);

            if (!$orderItem) {
                continue;
            }

            $orderItem->setQtyCaptured($orderItem->getQtyCaptured() + $qty);
            $this->entityManager->persist($orderItem);
            $this->entityManager->flush($orderItem);

            $totalAmount += ($orderItem->getUnitPrice() * $qty);
        }

        // Update totals
        $orderTotal = $this->getOrderTotalsRepository()->findByOrder($order);
        if ($orderTotal) {
            $orderTotal->setCapturedTotal(
                $orderTotal->getCapturedTotal() + $totalAmount
            );

            $this->entityManager->persist($orderTotal);
            $this->entityManager->flush($orderTotal);
        }
    }

    /**
     * Add refunded amount.
     * @param Order $order
     * @param float $amount
     * @param bool $isManual
     *
     * @return void
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addInvoicedAmount(Order $order, float $amount, bool $isManual)
    {
        $orderTotal = $this->getOrderTotalsRepository()->findByOrder($order);
        if (!$orderTotal) {
            throw new \Exception('Order totals is not found.');
        }

        $orderTotal->setInvoicedTotal($orderTotal->getInvoicedTotal() + $amount);
        if ($isManual) {
            $orderTotal->setManual(true);
        }

        $this->entityManager->persist($orderTotal);
        $this->entityManager->flush($orderTotal);
    }

    /**
     * Get available cancel amount.
     *
     * @param Order $order
     *
     * @return float
     */
    public function getAvailableCancelAmount(Order $order): float
    {
        $orderTotal = $this->getOrderTotalsRepository()->findByOrder($order);
        if (!$orderTotal) {
            return 0;
        }

        return $order->getTotal() - $orderTotal->getCancelledTotal() - $orderTotal->getCapturedTotal();
    }

    /**
     * Get available capture amount.
     *
     * @param Order $order
     *
     * @return float
     */
    public function getAvailableCaptureAmount(Order $order): float
    {
        $orderTotal = $this->getOrderTotalsRepository()->findByOrder($order);
        if (!$orderTotal) {
            return 0;
        }

        return $order->getTotal() - $orderTotal->getCancelledTotal() - $orderTotal->getCapturedTotal();
    }

    /**
     * Get available refund amount.
     *
     * @param Order $order
     *
     * @return float
     */
    public function getAvailableRefundAmount(Order $order): float
    {
        $orderTotal = $this->getOrderTotalsRepository()->findByOrder($order);
        if (!$orderTotal) {
            return 0;
        }

        return $orderTotal->getCapturedTotal() - $orderTotal->getRefundedTotal();
    }

    /**
     * Get available refund amount.
     *
     * @param Order $order
     *
     * @return float
     */
    public function getAvailableInvoicedAmount(Order $order): float
    {
        $orderTotal = $this->getOrderTotalsRepository()->findByOrder($order);
        if (!$orderTotal) {
            return 0;
        }

        return $orderTotal->getCapturedTotal() - $orderTotal->getInvoicedTotal();
    }

    private function getOrderItemsRepository(): OrderItemsRepository
    {
        return $this->doctrine
            ->getManagerForClass(OrderItems::class)
            ->getRepository(OrderItems::class);
    }

    private function getOrderTotalsRepository(): OrderTotalsRepository
    {
        return $this->doctrine
            ->getManagerForClass(OrderTotals::class)
            ->getRepository(OrderTotals::class);
    }
}
