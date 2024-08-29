<?php

namespace Payever\Bundle\PaymentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class OrderPaymentCancelType extends AbstractType
{
    use OrderPaymentTrait;

    const NAME = 'oro_order_cancel_widget';
}
