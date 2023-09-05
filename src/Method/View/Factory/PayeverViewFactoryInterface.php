<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\View\Factory;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfigInterface;

interface PayeverViewFactoryInterface
{
    /**
     * @param PayeverConfigInterface $config
     *
     * @return PaymentMethodViewInterface
     */
    public function create(PayeverConfigInterface $config);
}
