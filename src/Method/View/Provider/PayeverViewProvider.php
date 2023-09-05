<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\View\Provider;

use Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfigInterface;
use Payever\Bundle\PaymentBundle\Method\Config\Provider\PayeverConfigProviderInterface;
use Payever\Bundle\PaymentBundle\Method\View\Factory\PayeverViewFactoryInterface;

class PayeverViewProvider extends AbstractPaymentMethodViewProvider
{
    /** @var PayeverViewFactoryInterface */
    private $factory;

    /** @var PayeverConfigProviderInterface */
    private $configProvider;

    /**
     * @param PayeverConfigProviderInterface $configProvider
     * @param PayeverViewFactoryInterface $factory
     */
    public function __construct(
        PayeverConfigProviderInterface $configProvider,
        PayeverViewFactoryInterface $factory
    ) {
        $this->factory = $factory;
        $this->configProvider = $configProvider;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function buildViews(): void
    {
        $configs = $this->configProvider->getPaymentConfigs();
        foreach ($configs as $config) {
            $this->addPayeverView($config);
        }
    }

    /**
     * @param PayeverConfigInterface $config
     */
    protected function addPayeverView(PayeverConfigInterface $config): void
    {
        $this->addView(
            $config->getPaymentMethodIdentifier(),
            $this->factory->create($config)
        );
    }
}
