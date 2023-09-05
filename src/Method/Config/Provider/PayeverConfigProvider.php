<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\Config\Provider;

use Oro\Bundle\EntityBundle\ORM\Registry;
use Payever\Bundle\PaymentBundle\Entity\PayeverSettings;
use Payever\Bundle\PaymentBundle\Entity\Repository\PayeverSettingsRepository;
use Payever\Bundle\PaymentBundle\Method\Config\Factory\PayeverConfigFactoryInterface;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfigInterface;
use Psr\Log\LoggerInterface;

class PayeverConfigProvider implements PayeverConfigProviderInterface
{
    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @var PayeverConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * @var PayeverConfigInterface[]
     */
    protected $configs;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        Registry $doctrine,
        LoggerInterface $logger,
        PayeverConfigFactoryInterface $configFactory
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->configFactory = $configFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentConfigs()
    {
        $configs = [];

        $settings = $this->getEnabledIntegrationSettings();

        foreach ($settings as $setting) {
            $config = $this->configFactory->create($setting);

            $configs[$config->getPaymentMethodIdentifier()] = $config;
        }

        return $configs;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentConfig($identifier)
    {
        $paymentConfigs = $this->getPaymentConfigs();

        if ([] === $paymentConfigs || false === \array_key_exists($identifier, $paymentConfigs)) {
            return null;
        }

        return $paymentConfigs[$identifier];
    }

    /**
     * {@inheritDoc}
     */
    public function hasPaymentConfig($identifier)
    {
        return null !== $this->getPaymentConfig($identifier);
    }

    /**
     * @return PayeverSettings[]
     */
    protected function getEnabledIntegrationSettings()
    {
        try {
            /** @var PayeverSettingsRepository $repository */
            $repository = $this->doctrine
                ->getManagerForClass(PayeverSettings::class)
                ->getRepository(PayeverSettings::class);

            return $repository->getEnabledSettings();
        } catch (\UnexpectedValueException $e) {
            $this->logger->critical($e->getMessage());

            return [];
        }
    }
}
