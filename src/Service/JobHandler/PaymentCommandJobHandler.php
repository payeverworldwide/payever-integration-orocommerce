<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\JobHandler;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Payever\Sdk\Plugins\Command\PluginCommandManager;
use Payever\Sdk\Plugins\PluginsApiClient;
use Psr\Log\LoggerInterface;

class PaymentCommandJobHandler
{
    private ConfigManager $configManager;
    private PluginsApiClient $pluginsApiClient;
    private PluginCommandManager $pluginCommandManager;
    private LoggerInterface $logger;

    public function __construct(
        ConfigManager $configManager,
        PluginsApiClient $pluginsApiClient,
        PluginCommandManager $pluginCommandManager,
        LoggerInterface $logger
    ) {
        $this->configManager = $configManager;
        $this->pluginsApiClient = $pluginsApiClient;
        $this->pluginCommandManager = $pluginCommandManager;
        $this->logger = $logger;
    }

    public function run(): void
    {
        try {
            $timestamp = (int) $this->configManager->get('payever_payment.command_timestamp');

            $this->pluginsApiClient->registerPlugin();
            $this->pluginCommandManager->executePluginCommands($timestamp);

            $this->configManager->set('payever_payment.command_timestamp', time());
            $this->configManager->flush();
        } catch (\Exception $exception) {
            $this->logger->warning(sprintf('Plugin command execution failed: %s', $exception->getMessage()));
        }
    }
}
