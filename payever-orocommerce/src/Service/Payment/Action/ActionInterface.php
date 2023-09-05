<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Action;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Payever\Sdk\Core\Base\ResponseInterface;
use Payever\Sdk\Core\Http\ResponseEntity;

interface ActionInterface
{
    public function execute(Order $order, string $paymentId, $amount = null): ResponseEntity;
}
