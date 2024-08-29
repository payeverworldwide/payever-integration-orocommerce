<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfig;
use Payever\Bundle\PaymentBundle\Service\Payment\PaymentProcessorService;
use Psr\Log\LoggerInterface;

class PurchasePaymentAction implements PaymentActionInterface
{
    /**
     * @var PaymentProcessorService
     */
    private PaymentProcessorService $paymentProcessorService;

    private LoggerInterface $logger;

    public function __construct(
        PaymentProcessorService $paymentProcessorService,
        LoggerInterface $logger
    ) {
        $this->paymentProcessorService = $paymentProcessorService;
        $this->logger = $logger;
    }

    public function execute(
        PayeverConfig $config,
        PaymentTransaction $paymentTransaction
    ): array {
        $this->logger->debug('Purchase action');

        try {
            $redirectUrl = $this->paymentProcessorService
                ->setConfig($config)
                ->saveExternalId($paymentTransaction)
                ->getRedirectUrl($paymentTransaction);
        } catch (\Exception $exception) {
            $this->logger->critical('Purchase action error: ' . $exception->getMessage());

            return [
                'successful' => false,
                'error' => $exception->getMessage()
            ];
        }

        return [
            'purchaseRedirectUrl' => $redirectUrl
        ];
    }

    public function isApplicable(string $action, PaymentTransaction $paymentTransaction): bool
    {
        return $action === PaymentMethodInterface::PURCHASE;
    }
}
