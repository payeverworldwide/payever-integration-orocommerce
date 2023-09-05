<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Payever\Bundle\PaymentBundle\Constant\LogLevelConstant;
use Payever\Bundle\PaymentBundle\Constant\SettingsConstant;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const ROOT_NODE = 'payever_payment';

    /**
     * @inheritDoc
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'client_id' => ['value' => '2746_6abnuat5q10kswsk4ckk4ssokw4kgk8wow08sg0c8csggk4o00'],
                'client_secret' => ['value' => '2fjpkglmyeckg008oowckco4gscc4og4s0kogskk48k8o8wgsc'],
                'business_uuid' => ['value' => 'payever'],
                'is_redirect' => ['type' => 'boolean', 'value' => false],
                'mode' => ['value' => SettingsConstant::MODE_SANDBOX],
                'log_level' => ['value' => LogLevelConstant::DEBUG],
                'oauth_token' => ['value' => ''],
                'sandbox_url' => ['value' => ''],
                'live_url' => ['value' => ''],
            ]
        );

        return $treeBuilder;
    }
}
