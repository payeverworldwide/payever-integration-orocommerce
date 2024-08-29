<?php

namespace Payever\Bundle\PaymentBundle\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CheckoutBundle\Api\GuestCheckoutChecker;
use Oro\Bundle\CustomerBundle\Placeholder\PlaceholderFilter;
use Payever\Bundle\PaymentBundle\Constant\QueryConstant;
use Payever\Bundle\PaymentBundle\Constant\SettingsConstant;
use Payever\Bundle\PaymentBundle\Service\FinanceExpressConfig;
use Payever\Bundle\PaymentBundle\Service\Helper\OrderHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FinanceExpressProvider
{
    /**
     * @var FinanceExpressConfig
     */
    private FinanceExpressConfig $financeExpressConfig;

    /**
     * @var ConfigManager
     */
    private ConfigManager $configManager;

    /**
     * @var PlaceholderFilter
     */
    private PlaceholderFilter $placeholderFilter;

    /**
     * @var GuestCheckoutChecker
     */
    private $guestCheckoutChecker;

    /**
     * @var RouterInterface
     */
    private RouterInterface $router;

    /**
     * @var OrderHelper
     */
    private OrderHelper $orderHelper;

    /**
     * @var RequestStack
     */
    private RequestStack $requestStack;

    /**
     * Constructor.
     *
     * @param FinanceExpressConfig $financeExpressConfig
     * @param ConfigManager $configManager
     * @param PlaceholderFilter $placeholderFilter
     * @param GuestCheckoutChecker $guestCheckoutChecker,
     * @param RouterInterface $router
     * @param OrderHelper $orderHelper
     * @param RequestStack $requestStack
     */
    public function __construct(
        FinanceExpressConfig $financeExpressConfig,
        ConfigManager $configManager,
        PlaceholderFilter $placeholderFilter,
        GuestCheckoutChecker $guestCheckoutChecker,
        RouterInterface $router,
        OrderHelper $orderHelper,
        RequestStack $requestStack
    ) {
        $this->financeExpressConfig = $financeExpressConfig;
        $this->configManager = $configManager;
        $this->placeholderFilter = $placeholderFilter;
        $this->guestCheckoutChecker = $guestCheckoutChecker;
        $this->router = $router;
        $this->orderHelper = $orderHelper;
        $this->requestStack = $requestStack;
    }

    /**
     * Checks if the JS Widget must be added on the page.
     *
     * @return bool
     */
    public function isVisible(): bool
    {
        return $this->isVisibleOnProduct() || $this->isVisibleOnCart();
    }

    /**
     * Check if visible on the product page.
     *
     * @return bool
     */
    public function isVisibleOnProduct(): bool
    {
        if (
            $this->placeholderFilter->isLoginRequired() &&
            !$this->guestCheckoutChecker->isGuestWithEnabledCheckout()
        ) {
            return false;
        }

        if (!$this->financeExpressConfig->isPaymentMethodConfigured()) {
            return false;
        }

        return (bool) $this->configManager->get('payever_payment.fe_product');
    }

    /**
     * Check if visible on the shopping list page.
     *
     * @return bool
     */
    public function isVisibleOnCart(): bool
    {
        if (
            $this->placeholderFilter->isLoginRequired() &&
            !$this->guestCheckoutChecker->isGuestWithEnabledCheckout()
        ) {
            return false;
        }

        if (!$this->financeExpressConfig->isPaymentMethodConfigured()) {
            return false;
        }

        return (bool) $this->configManager->get('payever_payment.fe_cart');
    }

    /**
     * Get Settings for Product widget.
     *
     * @return array
     */
    public function getSettingsForProduct(): array
    {
        return [
            'data-widgetid'   => $this->financeExpressConfig->getWidgetId(),
            'data-theme'      => $this->financeExpressConfig->getWidgetTheme(),
            'data-checkoutid' => $this->financeExpressConfig->getCheckoutId(),
            'data-business'   => $this->financeExpressConfig->getBusinessId(),
            'data-type'       => $this->financeExpressConfig->getWidgetType(),
            'data-reference'  => uniqid('prod_' . $this->orderHelper->getReservedOrderIdentifier() . '_'),
            'data-cancelurl'  => $this->getCancelUrl(),
            'data-failureurl' => $this->getFailureUrl(),
            'data-pendingurl' => $this->getSuccessUrl(),
            'data-successurl' => $this->getSuccessUrl(),
            'data-noticeurl'  => $this->getNoticeUrl(),
            'data-quotecallbackurl' => $this->getQuoteCallbackUrl(),
        ];
    }

    /**
     * Get Settings for Cart widget.
     *
     * @return array
     */
    public function getSettingsForCart(): array
    {
        $settings = $this->getSettingsForProduct();
        $settings['data-reference'] = str_replace('prod_', 'cart_', $settings['data-reference']);

        return $settings;
    }

    /**
     * Check if the sandbox mode is active.
     *
     * @return string
     */
    public function isSandboxMode(): string
    {
        return SettingsConstant::MODE_SANDBOX === $this->configManager->get('payever_payment.mode');
    }

    /**
     * Get Order Reference.
     *
     * @return string
     */
    public function getOrderReference(): string
    {
        return (string) $this->requestStack->getCurrentRequest()->get(QueryConstant::PARAMETER_ORDER_REFERENCE);
    }

    /**
     * Get the success url callback.
     *
     * @return string
     */
    private function getSuccessUrl(): string
    {
        return $this->router->generate(
            'payever_payment_fe_success',
            [
                QueryConstant::PARAMETER_TYPE => QueryConstant::CALLBACK_TYPE_SUCCESS
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * Get the failure url callback.
     *
     * @return string
     */
    private function getFailureUrl(): string
    {
        return $this->router->generate(
            'payever_payment_fe_failure',
            [
                QueryConstant::PARAMETER_TYPE => QueryConstant::CALLBACK_TYPE_FAILURE
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * Get the cancel url callback.
     *
     * @return string
     */
    private function getCancelUrl(): string
    {
        return $this->router->generate(
            'payever_payment_fe_cancel',
            [
                QueryConstant::PARAMETER_TYPE => QueryConstant::CALLBACK_TYPE_CANCEL
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * Get the notice url callback.
     *
     * @return string
     */
    private function getNoticeUrl(): string
    {
        return $this->router->generate(
            'payever_payment_fe_notice',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * Get the quote url callback.
     *
     * @return string
     */
    private function getQuoteCallbackUrl(): string
    {
        return $this->router->generate(
            'payever_payment_fe_quote',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
