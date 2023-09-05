<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfigInterface;
use Payever\Bundle\PaymentBundle\Method\PaymentAction\PaymentActionRegistry;

class Payever implements PaymentMethodInterface
{
    /**
     * @var PayeverConfigInterface
     */
    private PayeverConfigInterface $config;

    /**
     * @var PaymentActionRegistry
     */
    private PaymentActionRegistry $paymentActionRegistry;

    public function __construct(
        PayeverConfigInterface $config,
        PaymentActionRegistry $paymentActionRegistry
    ) {
        $this->config = $config;
        $this->paymentActionRegistry = $paymentActionRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        if (!$this->supports($action)) {
            throw new \InvalidArgumentException(sprintf('Unsupported action "%s"', $action));
        }

        try {
            return $this->paymentActionRegistry->getPaymentAction($action, $paymentTransaction)
                ->execute($this->config, $paymentTransaction);
        } catch (\Exception $exception) {
            return [
                'successful' => false,
                'error' => $exception->getMessage()
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(PaymentContextInterface $context): bool
    {
        return $context->getTotal() <= $this->config->getAllowedMaxAmount() &&
            $context->getTotal() >= $this->config->getAllowedMinAmount();
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionName): bool
    {
        return in_array($actionName, [
            PaymentMethodInterface::PURCHASE,
            PaymentMethodInterface::CAPTURE,
            PaymentMethodInterface::CANCEL,
            PaymentMethodInterface::REFUND
        ]);
    }
}
