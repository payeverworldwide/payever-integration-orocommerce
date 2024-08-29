<?php

namespace Payever\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;

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
}
