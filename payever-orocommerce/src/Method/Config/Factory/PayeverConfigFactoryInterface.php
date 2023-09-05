<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\Config\Factory;

use Payever\Bundle\PaymentBundle\Entity\PayeverSettings;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfigInterface;

interface PayeverConfigFactoryInterface
{
    /**
     * @param PayeverSettings $settings
     *
     * @return PayeverConfigInterface
     */
    public function create(PayeverSettings $settings);
}
