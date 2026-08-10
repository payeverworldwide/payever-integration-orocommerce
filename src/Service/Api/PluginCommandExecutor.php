<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Api;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Payever\Sdk\Plugins\Command\AbstractPluginCommandExecutor;
use Payever\Sdk\Plugins\Enum\PluginCommandNameEnum;
use Payever\Sdk\Plugins\Http\MessageEntity\PluginCommandEntity;

class PluginCommandExecutor extends AbstractPluginCommandExecutor
{
    private ConfigManager $configManager;

    public function __construct(
        ConfigManager $configManager
    ) {
        $this->configManager = $configManager;
    }

    public function executeCommand(PluginCommandEntity $command)
    {
        $name = $command->getName();
        $value = $command->getValue();
        switch ($name) {
            case PluginCommandNameEnum::SET_SANDBOX_HOST:
                $this->assertApiHostValid($value);
                $this->configManager->set('payever_payment.sandbox_url', $value);
                $this->configManager->flush();
                break;
            case PluginCommandNameEnum::SET_LIVE_HOST:
                $this->assertApiHostValid($value);
                $this->configManager->set('payever_payment.live_url', $value);
                $this->configManager->flush();
                break;
            case PluginCommandNameEnum::SET_API_VERSION:
                $this->configManager->set('payever_payment.api_version', $value);
                $this->configManager->flush();
                break;
            default:
                throw new \UnexpectedValueException(
                    sprintf(
                        'Command %s with value %s is not supported',
                        $command->getId(),
                        $value
                    )
                );
        }
    }
}
