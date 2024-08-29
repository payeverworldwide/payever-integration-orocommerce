<?php

namespace Payever\Bundle\PaymentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class OrderPaymentRefundType extends AbstractType
{
    use OrderPaymentTrait;

    const NAME = 'oro_order_refund_widget';
}
