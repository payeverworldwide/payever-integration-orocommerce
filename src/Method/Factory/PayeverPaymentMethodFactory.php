<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\Factory;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfigInterface;
use Payever\Bundle\PaymentBundle\Method\Payever;
use Payever\Bundle\PaymentBundle\Method\PaymentAction\PaymentActionRegistry;
use Payever\Bundle\PaymentBundle\Service\Company\CompanyCreditService;
use Psr\Log\LoggerInterface;
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
     * @var Session
     */
    private Session $session;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct(
        PaymentActionRegistry $paymentActionRegistry,
        ConfigManager $configManager,
        CompanyCreditService $companyCreditService,
        Session $session,
        LoggerInterface $logger
    ) {
        $this->paymentActionRegistry = $paymentActionRegistry;
        $this->configManager = $configManager;
        $this->companyCreditService = $companyCreditService;
        $this->session = $session;
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
            $this->session,
            $this->logger
        );
    }
}
