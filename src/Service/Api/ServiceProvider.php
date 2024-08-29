<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Api;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Payever\Bundle\PaymentBundle\Constant\SettingsConstant;
use Payever\Sdk\Core\ClientConfiguration;
use Payever\Sdk\Core\Enum\ChannelSet;
use Payever\Sdk\Payments\PaymentsApiClient;
use Payever\Sdk\Payments\ThirdPartyPluginsApiClient;
use Payever\Sdk\Payments\WidgetsApiClient;
use Psr\Log\LoggerInterface;

class ServiceProvider
{
    /**
     * @var TokenList
     */
    private TokenList $tokenList;

    private ConfigManager $configManager;

    private LoggerInterface $logger;

    /**
     * @var PaymentsApiClient|null
     */
    private $paymentsApiClient;

    /**
     * @var ClientConfiguration|null
     */
    private $clientConfiguration;

    public function __construct(
        TokenList $tokenList,
        ConfigManager $configManager,
        LoggerInterface $logger
    ) {
        $this->tokenList = $tokenList;
        $this->configManager = $configManager;
        $this->logger = $logger;
    }

    public function getPaymentsApiClient(): PaymentsApiClient
    {
        if (!$this->paymentsApiClient) {
            $this->paymentsApiClient = new PaymentsApiClient(
                $this->getClientConfiguration(),
                $this->tokenList
            );

            // Set configured log level
            $this->paymentsApiClient
                ->getHttpClient()
                ->setLogLevel($this->configManager->get('payever_payment.log_level'));
        }

        return $this->paymentsApiClient;
    }

    /**
     * @return ThirdPartyPluginsApiClient
     */
    public function getThirdPartyPluginsApiClient(): ThirdPartyPluginsApiClient
    {
        return new ThirdPartyPluginsApiClient(
            $this->getClientConfiguration(),
            $this->tokenList
        );
    }

    /**
     * @return WidgetsApiClient
     * @codeCoverageIgnore
     */
    public function getPaymentWidgetsApiClient()
    {
        return new WidgetsApiClient(
            $this->getClientConfiguration(),
            $this->tokenList
        );
    }

    /**
     * Check if access token is valid.
     *
     * @return bool
     */
    public function isAccessTokenValid(string $accessToken): bool
    {
        return $this->getThirdPartyPluginsApiClient()->validateToken(
            $this->getBusinessUuid(),
            $accessToken
        );
    }

    /**
     * Set API Credentials.
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $businessUuid
     * @param string  $mode
     *
     * @return $this
     */
    public function setApiCredentials(string $clientId, string $clientSecret, string $businessUuid, string $mode): self
    {
        $this->configManager->set('payever_payment.client_id', $clientId);
        $this->configManager->set('payever_payment.client_secret', $clientSecret);
        $this->configManager->set('payever_payment.business_uuid', $businessUuid);
        $this->configManager->set('payever_payment.mode', $mode);
        $this->configManager->flush();

        return $this;
    }

    private function getClientConfiguration(): ClientConfiguration
    {
        if (!$this->clientConfiguration) {
            $this->clientConfiguration = $this->loadClientConfiguration();
        }

        return $this->clientConfiguration;
    }

    private function loadClientConfiguration(): ClientConfiguration
    {
        $clientConfiguration = new ClientConfiguration();

        $apiMode = SettingsConstant::MODE_SANDBOX === $this->configManager->get('payever_payment.mode')
            ? ClientConfiguration::API_MODE_SANDBOX
            : ClientConfiguration::API_MODE_LIVE;

        $clientConfiguration->setChannelSet(ChannelSet::CHANNEL_OROCOMMERCE)
            ->setApiMode($apiMode)
            ->setClientId($this->configManager->get('payever_payment.client_id'))
            ->setClientSecret($this->configManager->get('payever_payment.client_secret'))
            ->setBusinessUuid($this->getBusinessUuid())
            ->setLogger($this->logger);

        $sandboxUrl = $this->configManager->get('payever_payment.sandbox_url');
        if ($sandboxUrl) {
            $this->logger->debug('Use ' . $sandboxUrl);
            $clientConfiguration->setCustomSandboxUrl($sandboxUrl);
        }

        $liveUrl = $this->configManager->get('payever_payment.live_url');
        if ($liveUrl) {
            $this->logger->debug('Use ' . $liveUrl);
            $clientConfiguration->setCustomLiveUrl($liveUrl);
        }

        return $clientConfiguration;
    }

    private function getBusinessUuid(): ?string
    {
        return $this->configManager->get('payever_payment.business_uuid');
    }
}
