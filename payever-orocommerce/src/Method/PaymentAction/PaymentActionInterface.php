<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfig;
use Payever\Sdk\Core\Http\ResponseEntity;

interface PaymentActionInterface
{
    public function execute(
        PayeverConfig $config,
        PaymentTransaction $paymentTransaction
    ): array;

    public function isApplicable(string $action, PaymentTransaction $paymentTransaction): bool;
}
