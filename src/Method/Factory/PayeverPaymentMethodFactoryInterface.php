<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\Factory;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfigInterface;

interface PayeverPaymentMethodFactoryInterface
{
    /**
     * @param PayeverConfigInterface $config
     *
     * @return PaymentMethodInterface
     */
    public function create(PayeverConfigInterface $config);
}
