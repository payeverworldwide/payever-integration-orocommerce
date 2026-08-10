<?php

namespace Payever\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\OrderBundle\Entity\Order;
use Payever\Bundle\PaymentBundle\Entity\OrderInvoice;

class OrderInvoiceRepository extends ServiceEntityRepository
{
    /**
     * @return int
     */
    public function getLastInvoiceNumber(): int
    {
        $row = $this->findOneBy([], ['id' => Criteria::DESC]);

        return $row ? $row->getId() : 0;
    }

    /**
     * @param Order $order
     *
     * @return OrderInvoice[]
     */
    public function findByOrder(Order $order): array
    {
        return $this->findBy(['orderId' => $order->getId()], ['id' => Criteria::ASC]);
    }
}
