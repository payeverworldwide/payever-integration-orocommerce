<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Factory;

use Payever\Bundle\PaymentBundle\Entity\PaymentAction;

class PaymentActionFactory
{
    /**
     * @var PaymentAction
     */
    private PaymentAction $paymentAction;

    public function __construct(PaymentAction $paymentAction)
    {
        $this->paymentAction = $paymentAction;
    }

    /**
     * Creates a payment action object.
     *
     * @return PaymentAction A PaymentAction object.
     */
    public function create(): PaymentAction
    {
        return clone $this->paymentAction;
    }
}
