<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Api;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Payever\Bundle\PaymentBundle\Constant\SettingsConstant;
use Payever\Sdk\Core\Authorization\ApmSecretService as BaseApmSecretService;

class ApmSecretService extends BaseApmSecretService
{
    private configManager $configManager;

    public function __construct(
        ConfigManager $configManager
    ) {
        $this->configManager = $configManager;
    }

    /**
     * @return string|null
     */
    public function get()
    {
        if (SettingsConstant::MODE_SANDBOX === $this->configManager->get('payever_payment.mode')) {
            return $this->configManager->get('payever_payment.apm_secret_sandbox') ?: null;
        }

        return $this->configManager->get('payever_payment.apm_secret_live') ?: null;
    }

    /**
     * @param string $apmSecret
     * @return self
     */
    public function save($apmSecret): self
    {
        $result = parent::save($apmSecret);
        if (empty($apmSecret)) {
            return $result;
        }

        if (SettingsConstant::MODE_SANDBOX === $this->configManager->get('payever_payment.mode')) {
            $this->configManager->set('payever_payment.apm_secret_sandbox', $apmSecret);
            $this->configManager->flush();

            return $this;
        }

        $this->configManager->set('payever_payment.apm_secret_live', $apmSecret);
        $this->configManager->flush();

        return $this;
    }
}
