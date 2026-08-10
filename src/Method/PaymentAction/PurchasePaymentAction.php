<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfig;
use Payever\Bundle\PaymentBundle\Service\Helper\TransactionHelper;
use Payever\Bundle\PaymentBundle\Service\Management\CheckoutManager;
use Payever\Bundle\PaymentBundle\Service\Payment\PaymentProcessorService;
use Psr\Log\LoggerInterface;

class PurchasePaymentAction implements PaymentActionInterface
{
    /**
     * @var PaymentProcessorService
     */
    private PaymentProcessorService $paymentProcessorService;

    /**
     * @var CheckoutManager
     */
    private CheckoutManager $checkoutManager;

    /**
     * @var TransactionHelper
     */
    private TransactionHelper $transactionHelper;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param PaymentProcessorService $paymentProcessorService
     * @param CheckoutManager $checkoutManager
     * @param TransactionHelper $transactionHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        PaymentProcessorService $paymentProcessorService,
        CheckoutManager $checkoutManager,
        TransactionHelper $transactionHelper,
        LoggerInterface $logger
    ) {
        $this->paymentProcessorService = $paymentProcessorService;
        $this->checkoutManager = $checkoutManager;
        $this->transactionHelper = $transactionHelper;
        $this->logger = $logger;
    }

    public function execute(
        PayeverConfig $config,
        PaymentTransaction $paymentTransaction
    ): array {
        $this->logger->debug('Purchase action');

        $options = $paymentTransaction->getTransactionOptions();
        if (empty($options['checkoutId'])) {
            return [
                'successful' => false,
                'error' => 'Checkout ID is missing',
            ];
        }

        $payeverCheckout = $this->checkoutManager->getPayeverCheckout($options['checkoutId']);

        // Check if payment already processed
        if ($payeverCheckout?->getPaymentId()) {
            return ['successful' => true];
        }

        try {
            $order = $this->transactionHelper->getOrder($paymentTransaction);
            $checkout = $this->checkoutManager->getOroCheckout($options['checkoutId']);

            $redirectUrl = $this->paymentProcessorService->getPaymentUrl($order, $checkout, $config);
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
