<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

class PayeverChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    public const TYPE = 'payever';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'payever.channel_type.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon()
    {
        return 'bundles/payeverpayment/img/icon_payever.png';
    }
}
