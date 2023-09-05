<?php

namespace Payever\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Oro\Bundle\OrderBundle\Entity\Order;
use Payever\Bundle\PaymentBundle\Entity\OrderTotals;

class OrderTotalsRepository extends ServiceEntityRepository
{
    /**
     * @param Order $order
     *
     * @return OrderTotals|null
     */
    public function findByOrder(Order $order): ?OrderTotals
    {
        return $this->findOneBy(['orderId' => $order->getId()]);
    }
}
