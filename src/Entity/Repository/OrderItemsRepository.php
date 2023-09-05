<?php

namespace Payever\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Oro\Bundle\OrderBundle\Entity\Order;
use Payever\Bundle\PaymentBundle\Entity\OrderItems;

class OrderItemsRepository extends ServiceEntityRepository
{
    /**
     * @param Order $order
     *
     * @return OrderItems[]
     */
    public function findByOrder(Order $order): array
    {
        return $this->findBy(['orderId' => $order->getId()]);
    }

    /**
     * @param Order $order
     * @param string $itemReference
     *
     * @return OrderItems|null
     */
    public function findByItemReference(Order $order, string $itemReference): ?OrderItems
    {
        return $this->findOneBy(['orderId' => $order->getId(), 'itemReference' => $itemReference]);
    }

    /**
     * @param Order $order
     * @param string $itemType
     *
     * @return OrderItems|null
     */
    public function findByItemType(Order $order, string $itemType): ?OrderItems
    {
        return $this->findOneBy(['orderId' => $order->getId(), 'itemType' => $itemType]);
    }
}
