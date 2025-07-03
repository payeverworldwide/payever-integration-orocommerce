<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfigInterface;
use Payever\Bundle\PaymentBundle\Method\PaymentAction\PaymentActionRegistry;
use Payever\Bundle\PaymentBundle\Service\Company\CompanyCreditService;
use Payever\Sdk\Payments\Enum\PaymentMethod;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class Payever implements PaymentMethodInterface
{
    const CHECKOUT_ACTION_ROUTE = '/checkout/';

    /**
     * @var PayeverConfigInterface
     */
    private PayeverConfigInterface $config;

    /**
     * @var ConfigManager
     */
    private ConfigManager $configManager;

    /**
     * @var PaymentActionRegistry
     */
    private PaymentActionRegistry $paymentActionRegistry;

    /**
     * @var CompanyCreditService
     */
    private CompanyCreditService $companyCreditService;

    /**
     * @var RequestStack
     */
    private RequestStack $requestStack;

    private LoggerInterface $logger;

    /**
     * @param PayeverConfigInterface $config
     * @param ConfigManager $configManager
     * @param PaymentActionRegistry $paymentActionRegistry
     * @param CompanyCreditService $companyCreditService
     * @param RequestStack $requestStack
     * @param LoggerInterface $logger
     */
    public function __construct(
        PayeverConfigInterface $config,
        ConfigManager $configManager,
        PaymentActionRegistry $paymentActionRegistry,
        CompanyCreditService $companyCreditService,
        RequestStack $requestStack,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->configManager = $configManager;
        $this->paymentActionRegistry = $paymentActionRegistry;
        $this->companyCreditService = $companyCreditService;
        $this->requestStack = $requestStack;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        if (!$this->supports($action)) {
            throw new \InvalidArgumentException(sprintf('Unsupported action "%s"', $action));
        }

        try {
            return $this->paymentActionRegistry->getPaymentAction($action, $paymentTransaction)
                ->execute($this->config, $paymentTransaction);
        } catch (\Exception $exception) {
            return [
                'successful' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(PaymentContextInterface $context): bool
    {
        if ($this->isCheckoutRequest() && $this->isHiddenMethod()) {
            $this->logger->debug(
                sprintf(
                    'Payment method "%s" has been hidden. Reason: %s',
                    $this->getIdentifier(),
                    'Hidden method'
                )
            );

            return false;
        }

        if ($this->config->getIsB2BMethod() && $this->shouldHideB2BMethod($context)) {
            return false;
        }

        $result = $context->getTotal() <= $this->config->getAllowedMaxAmount() &&
            $context->getTotal() >= $this->config->getAllowedMinAmount();
        if (!$result) {
            $this->logger->debug(
                sprintf(
                    'Payment method "%s" has been hidden. Reason: %s',
                    $this->getIdentifier(),
                    'Total limits: ' . json_encode([$context->getTotal(), $this->config->getAllowedMinAmount(), $this->config->getAllowedMaxAmount()]) //phpcs:ignore
                )
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionName): bool
    {
        return in_array($actionName, [
            PaymentMethodInterface::PURCHASE,
            PaymentMethodInterface::CAPTURE,
            PaymentMethodInterface::CANCEL,
            PaymentMethodInterface::REFUND,
        ]);
    }

    /**
     * Check if its frontend request
     *
     * @return bool
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private function isCheckoutRequest(): bool
    {
        // @todo Use Symphony framework instead of the superglobals
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($requestUri, self::CHECKOUT_ACTION_ROUTE) !== false) {
            return true;
        }

        return false;
    }

    /**
     * @param PaymentContextInterface $context
     * @return bool
     */
    private function shouldHideB2BMethod(PaymentContextInterface $context): bool
    {
        $companyId = $context->getBillingAddress()->getPayeverExternalId();
        if (!$companyId) {
            $this->logger->debug(
                sprintf(
                    'Payment method "%s" has been hidden. Reason: %s',
                    $this->getIdentifier(),
                    'Missing company id'
                )
            );

            // @todo Hide method is company ID is missing
            return false;
            //return true;
        }

        // Check company limits
        if ($this->configManager->get('payever_payment.b2b_company_credit_line')) {
            $creditData = $this->companyCreditService->getCompanyCredit($companyId);
            if (!$creditData) {
                $this->logger->debug(
                    sprintf(
                        'Payment method "%s" has been hidden. Reason: %s',
                        $this->getIdentifier(),
                        'Unable to obtain a credit line'
                    )
                );

                return true;
            }

            if ($context->getTotal() > (float) $creditData->getMaxInvoiceAmount()) {
                $this->logger->debug(
                    sprintf(
                        'Payment method "%s" has been hidden. Reason: %s',
                        $this->getIdentifier(),
                        'Max invoice amount'
                    )
                );

                return true;
            }
        }

        return false;
    }

    /**
     * Check if payment should be hidden
     *
     * @return bool
     */
    private function isHiddenMethod(): bool
    {
        return PaymentMethod::shouldHideOnCurrentDevice($this->config->getPaymentMethod());
    }
}
