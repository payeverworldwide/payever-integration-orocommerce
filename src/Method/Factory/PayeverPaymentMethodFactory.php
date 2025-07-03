<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\Factory;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfigInterface;
use Payever\Bundle\PaymentBundle\Method\Payever;
use Payever\Bundle\PaymentBundle\Method\PaymentAction\PaymentActionRegistry;
use Payever\Bundle\PaymentBundle\Service\Company\CompanyCreditService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class PayeverPaymentMethodFactory implements PayeverPaymentMethodFactoryInterface
{
    /**
     * @var PaymentActionRegistry
     */
    private PaymentActionRegistry $paymentActionRegistry;

    /**
     * @var ConfigManager
     */
    private ConfigManager $configManager;

    /**
     * @var CompanyCreditService
     */
    private CompanyCreditService $companyCreditService;

    /**
     * @var RequestStack
     */
    private RequestStack $requestStack;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct(
        PaymentActionRegistry $paymentActionRegistry,
        ConfigManager $configManager,
        CompanyCreditService $companyCreditService,
        RequestStack $requestStack,
        LoggerInterface $logger
    ) {
        $this->paymentActionRegistry = $paymentActionRegistry;
        $this->configManager = $configManager;
        $this->companyCreditService = $companyCreditService;
        $this->requestStack = $requestStack;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function create(PayeverConfigInterface $config)
    {
        return new Payever(
            $config,
            $this->configManager,
            $this->paymentActionRegistry,
            $this->companyCreditService,
            $this->requestStack,
            $this->logger
        );
    }
}
