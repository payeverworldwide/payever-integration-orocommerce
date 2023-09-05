<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Factory;

use Payever\Bundle\PaymentBundle\Entity\OrderItems;

class OrderItemFactory
{
    /**
     * @var OrderItems
     */
    private OrderItems $orderItem;

    public function __construct(OrderItems $orderItem)
    {
        $this->orderItem = $orderItem;
    }

    public function create(): OrderItems
    {
        return clone $this->orderItem;
    }
}
