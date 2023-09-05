<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\View;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfigInterface;

class PayeverView implements PaymentMethodViewInterface
{
    /**
     * @var PayeverConfigInterface
     */
    protected $config;

    /**
     * @param PayeverConfigInterface $config
     */
    public function __construct(PayeverConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(PaymentContextInterface $context)
    {
        return [
            'total_value' => $context->getTotal(),
            'payment_method' => $this->config->getPaymentMethod(),
            'description_fee' => $this->config->getDescriptionFee(),
            'description_offer' => $this->config->getDescriptionOffer(),
            'instruction_text' => $this->config->getInstructionText(),
            'thumbnail' => $this->config->getThumbnail(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getBlock()
    {
        return '_payment_methods_payever_widget';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->config->getLabel();
    }

    /**
     * {@inheritdoc}
     */
    public function getShortLabel()
    {
        return $this->config->getShortLabel();
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminLabel()
    {
        return $this->config->getAdminLabel();
    }

    /** {@inheritdoc} */
    public function getPaymentMethodIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }
}
