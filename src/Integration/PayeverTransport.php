<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Integration;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Payever\Bundle\PaymentBundle\Entity\PayeverSettings;
use Payever\Bundle\PaymentBundle\Form\Type\PayeverSettingsType;

class PayeverTransport implements TransportInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Transport $transportEntity): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'payever.settings.transport.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsFormType()
    {
        return PayeverSettingsType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsEntityFQCN()
    {
        return PayeverSettings::class;
    }
}
