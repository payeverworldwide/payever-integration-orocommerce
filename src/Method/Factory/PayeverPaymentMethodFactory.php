<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\Factory;

use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfigInterface;
use Payever\Bundle\PaymentBundle\Method\Payever;
use Payever\Bundle\PaymentBundle\Method\PaymentAction\PaymentActionRegistry;
use Payever\Bundle\PaymentBundle\Service\Payment\PaymentProcessorService;

class PayeverPaymentMethodFactory implements PayeverPaymentMethodFactoryInterface
{
    /**
     * @var PaymentActionRegistry
     */
    private PaymentActionRegistry $paymentActionRegistry;

    public function __construct(
        PaymentActionRegistry $paymentActionRegistry
    ) {
        $this->paymentActionRegistry = $paymentActionRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function create(PayeverConfigInterface $config)
    {
        return new Payever(
            $config,
            $this->paymentActionRegistry
        );
    }
}
