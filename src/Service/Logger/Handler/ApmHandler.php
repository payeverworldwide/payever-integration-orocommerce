<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Logger\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Payever\Bundle\PaymentBundle\Constant\SettingsConstant;
use Payever\Bundle\PaymentBundle\Service\Api\ApmSecretService;
use Payever\Sdk\Core\Apm\ApmApiClient;
use Payever\Sdk\Core\ClientConfiguration;
use Payever\Sdk\Core\Enum\ChannelSet;
use Psr\Log\LogLevel;

class ApmHandler extends AbstractProcessingHandler
{
    /**
     * @var ConfigManager|null
     */
    private ?ConfigManager $configManager;

    /**
     * @var ApmSecretService|null
     */
    private ?ApmSecretService $apmSecretService;

    private $apmApiClient;

    /**
     * Set Config Manager.
     *
     * @param ConfigManager $configManager
     * @return $this
     */
    public function setConfigManager(ConfigManager $configManager): self
    {
        $this->configManager = $configManager;

        return $this;
    }

    /**
     * Set APM Secret Service.
     *
     * @param ApmSecretService $apmSecretService
     * @return $this
     */
    public function setApmSecretService(ApmSecretService $apmSecretService): self
    {
        $this->apmSecretService = $apmSecretService;

        return $this;
    }

    public function setApmApiClient(ApmApiClient $apmApiClient): self
    {
        $this->apmApiClient = $apmApiClient;

        return $this;
    }

    /**
     * @return ApmApiClient
     * @throws \Exception
     */
    public function getApmApiClient(): ApmApiClient
    {
        if (!$this->apmApiClient) {
            $this->apmApiClient = new ApmApiClient($this->getClientConfiguration());
        }

        return $this->apmApiClient;
    }

    /**
     * @iheritdoc
     * @param array $record
     * @return void
     * @throws \Exception
     */
    protected function write(array $record): void
    {
        if ($this->configManager && $this->apmSecretService && $this->isLogDiagnosticEnabled()) {
            $message = $record['message'];
            $logLevel = strtolower($record['level_name']);
            if ($logLevel != LogLevel::CRITICAL && $logLevel != LogLevel::ERROR) {
                return;
            }

            if ($record['context']) {
                $message .= ' ' . json_encode($record['context']);
            }

            $this->getApmApiClient()->sendLog($message, $logLevel);
        }
    }

    /**
     * @return bool
     */
    private function isLogDiagnosticEnabled(): bool
    {
        return $this->configManager->get('payever_payment.enable_apm');
    }

    /**
     * @return ClientConfiguration
     * @throws \Exception
     */
    private function getClientConfiguration(): ClientConfiguration
    {
        $clientConfiguration = new ClientConfiguration();

        $apiMode = SettingsConstant::MODE_SANDBOX === $this->configManager->get('payever_payment.mode')
            ? ClientConfiguration::API_MODE_SANDBOX
            : ClientConfiguration::API_MODE_LIVE;

        $clientConfiguration->setChannelSet(ChannelSet::CHANNEL_OROCOMMERCE)
            ->setApiMode($apiMode)
            ->setClientId($this->configManager->get('payever_payment.client_id'))
            ->setClientSecret($this->configManager->get('payever_payment.client_secret'))
            ->setBusinessUuid($this->configManager->get('payever_payment.business_uuid'))
            ->setLogDiagnostic($this->isLogDiagnosticEnabled())
            ->setApmSecretService($this->apmSecretService);

        return $clientConfiguration;
    }
}
