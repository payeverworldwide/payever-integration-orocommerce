<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Action;

use Oro\Bundle\OrderBundle\Entity\Order;
use Payever\Sdk\Core\Http\ResponseEntity;

interface ActionInterface
{
    /**
     * Execute action by amount.
     *
     * @param Order $order
     * @param float|null $amount
     *
     * @return ResponseEntity
     */
    public function execute(Order $order, ?float $amount): ResponseEntity;

    /**
     * Execute action by item.
     *
     * @param Order $order
     * @param array $items
     *
     * @return ResponseEntity
     */
    public function executeItems(Order $order, array $items): ResponseEntity;
}
