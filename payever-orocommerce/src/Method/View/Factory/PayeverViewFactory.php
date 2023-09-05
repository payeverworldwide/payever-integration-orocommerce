<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\View\Factory;

use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfigInterface;
use Payever\Bundle\PaymentBundle\Method\View\PayeverView;

class PayeverViewFactory implements PayeverViewFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(PayeverConfigInterface $config)
    {
        return new PayeverView($config);
    }
}
