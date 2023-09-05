<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\Provider\AbstractPaymentMethodProvider;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfigInterface;
use Payever\Bundle\PaymentBundle\Method\Config\Provider\PayeverConfigProviderInterface;
use Payever\Bundle\PaymentBundle\Method\Factory\PayeverPaymentMethodFactoryInterface;

class PayeverMethodProvider extends AbstractPaymentMethodProvider
{
    /**
     * @var PayeverPaymentMethodFactoryInterface
     */
    protected $factory;

    /**
     * @var PayeverConfigProviderInterface
     */
    private $configProvider;

    /**
     * @param PayeverConfigProviderInterface $configProvider
     * @param PayeverPaymentMethodFactoryInterface $factory
     */
    public function __construct(
        PayeverConfigProviderInterface $configProvider,
        PayeverPaymentMethodFactoryInterface $factory
    ) {
        parent::__construct();

        $this->configProvider = $configProvider;
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    protected function collectMethods(): void
    {
        $configs = $this->configProvider->getPaymentConfigs();
        foreach ($configs as $config) {
            $this->addPayeverMethod($config);
        }
    }

    /**
     * @param PayeverConfigInterface $config
     */
    protected function addPayeverMethod(PayeverConfigInterface $config): void
    {
        $this->addMethod(
            $config->getPaymentMethodIdentifier(),
            $this->factory->create($config)
        );
    }
}
