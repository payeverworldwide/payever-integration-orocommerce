<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Factory;

use Payever\Bundle\PaymentBundle\Entity\OrderTotals;

class OrderTotalFactory
{
    /**
     * @var OrderTotals
     */
    private OrderTotals $orderTotal;

    public function __construct(OrderTotals $orderTotal)
    {
        $this->orderTotal = $orderTotal;
    }

    public function create(): OrderTotals
    {
        return clone $this->orderTotal;
    }
}
