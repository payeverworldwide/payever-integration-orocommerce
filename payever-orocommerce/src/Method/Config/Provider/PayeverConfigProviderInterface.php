<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\Config\Provider;

use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfigInterface;

/**
 * Interface for config provider which allows to get configs based on payment method identifier
 */
interface PayeverConfigProviderInterface
{
    /**
     * @return PayeverConfigInterface[]
     */
    public function getPaymentConfigs();

    /**
     * @param string $identifier
     *
     * @return PayeverConfigInterface|null
     */
    public function getPaymentConfig($identifier);

    /**
     * @param string $identifier
     *
     * @return bool
     */
    public function hasPaymentConfig($identifier);
}
