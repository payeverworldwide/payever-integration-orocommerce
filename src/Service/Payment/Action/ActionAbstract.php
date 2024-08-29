<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Action;

use Payever\Bundle\PaymentBundle\Service\Api\ServiceProvider;
use Payever\Bundle\PaymentBundle\Service\Helper\OrderHelper;
use Payever\Bundle\PaymentBundle\Service\Management\OrderManager;
use Payever\Bundle\PaymentBundle\Service\Management\PaymentActionManager;
use Payever\Sdk\Payments\PaymentsApiClient;
use Psr\Log\LoggerInterface;

abstract class ActionAbstract
{
    /**
     * @var ServiceProvider
     */
    protected ServiceProvider $serviceProvider;

    /**
     * @var PaymentActionManager
     */
    protected PaymentActionManager $paymentActionManager;

    /**
     * @var OrderManager
     */
    protected OrderManager $orderManager;

    /**
     * @var OrderHelper
     */
    protected OrderHelper $orderHelper;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @param ServiceProvider $serviceProvider
     * @param OrderManager $orderManager
     * @param OrderHelper $orderHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        ServiceProvider $serviceProvider,
        PaymentActionManager $paymentActionManager,
        OrderManager $orderManager,
        OrderHelper $orderHelper,
        LoggerInterface $logger
    ) {
        $this->serviceProvider = $serviceProvider;
        $this->paymentActionManager = $paymentActionManager;
        $this->orderManager = $orderManager;
        $this->orderHelper = $orderHelper;
        $this->logger = $logger;
    }

    /**
     * @return PaymentsApiClient
     */
    protected function getPaymentApiClient(): PaymentsApiClient
    {
        return $this->serviceProvider->getPaymentsApiClient();
    }
}
