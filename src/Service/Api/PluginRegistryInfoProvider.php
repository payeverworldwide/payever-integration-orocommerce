<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Api;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Payever\Bundle\PaymentBundle\Service\Helper\DataHelper;
use Payever\Sdk\Core\Enum\ChannelSet;
use Payever\Sdk\Plugins\Base\PluginRegistryInfoProviderInterface;
use Payever\Sdk\Plugins\Enum\PluginCommandNameEnum;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class PluginRegistryInfoProvider implements PluginRegistryInfoProviderInterface
{
    private DataHelper $dataHelper;
    private ConfigManager $configManager;
    private RouterInterface $router;

    public function __construct(
        DataHelper $dataHelper,
        ConfigManager $configManager,
        RouterInterface $router
    ) {
        $this->dataHelper = $dataHelper;
        $this->configManager = $configManager;
        $this->router = $router;
    }

    /**
     * @inheritDoc
     */
    public function getPluginVersion(): string
    {
        return $this->dataHelper->getPluginVersion();
    }

    /**
     * @inheritDoc
     */
    public function getCmsVersion(): string
    {
        return $this->dataHelper->getCmsVersion();
    }

    /**
     * @inheritDoc
     */
    public function getHost(): string
    {
        return $this->router->generate(
            'oro_frontend_root',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @inheritDoc
     */
    public function getChannel(): string
    {
        return ChannelSet::CHANNEL_OROCOMMERCE;
    }

    /**
     * @inheritDoc
     */
    public function getSupportedCommands(): array
    {
        return [
            PluginCommandNameEnum::SET_LIVE_HOST,
            PluginCommandNameEnum::SET_SANDBOX_HOST,
            PluginCommandNameEnum::NOTIFY_NEW_PLUGIN_VERSION,
            PluginCommandNameEnum::SET_API_VERSION,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getCommandEndpoint(): string
    {
        return $this->router->generate(
            'payever_payment_execute_commands',
            [],
            RouterInterface::ABSOLUTE_URL
        );
    }

    /**
     * @inheritDoc
     */
    public function getBusinessIds(): array
    {
        try {
            $businessUuid = $this->configManager->get('payever_payment.business_uuid');
        } catch (\Exception $exception) {
            // settings are not filled in yet
            $businessUuid = '';
        }

        return [$businessUuid];
    }
}
