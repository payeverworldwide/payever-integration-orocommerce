<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Api;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Payever\Sdk\Core\Authorization\OauthToken;
use Payever\Sdk\Core\Authorization\OauthTokenList;

class TokenList extends OauthTokenList
{
    private configManager $configManager;

    public function __construct(
        ConfigManager $configManager
    ) {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function load()
    {
        $savedTokens = $this->configManager->get('payever_payment.oauth_token');
        $savedTokens = is_string($savedTokens) ? json_decode($savedTokens, true) : null;

        if (is_array($savedTokens)) {
            foreach ($savedTokens as $name => $token) {
                $this->add(
                    $name,
                    $this->create()->load($token)
                );
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $savedTokens = [];

        /** @var OauthToken $token */
        foreach ($this->getAll() as $name => $token) {
            $savedTokens[$name] = $token->getParams();
        }

        $this->configManager->set('payever_payment.oauth_token', json_encode($savedTokens));
        $this->configManager->flush();

        return $this;
    }
}
