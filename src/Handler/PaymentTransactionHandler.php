<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Handler;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Payever\Bundle\PaymentBundle\Service\Payment\PaymentProcessorService;
use Symfony\Component\Form\FormInterface;

class PaymentTransactionHandler
{
    /**
     * @var PaymentMethodProviderInterface
     */
    private PaymentMethodProviderInterface $paymentMethodProvider;

    /**
     * @var PaymentProcessorService
     */
    private PaymentProcessorService $paymentProcessor;

    public function __construct(
        PaymentMethodProviderInterface $paymentMethodProvider,
        PaymentProcessorService $paymentProcessor
    ) {
        $this->paymentMethodProvider = $paymentMethodProvider;
        $this->paymentProcessor = $paymentProcessor;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @param FormInterface $form
     * @return bool
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function refund(PaymentTransaction $paymentTransaction, FormInterface $form): bool
    {
        if (empty($paymentTransaction->getReference())) {
            throw new \Exception('Payment reference is empty');
        }

        // @todo Refund handling
        throw new \Exception('Refund is pending.');
    }
}
