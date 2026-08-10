<?php

namespace Payever\Bundle\PaymentBundle\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PaymentBundle\Method\Provider\ApplicablePaymentMethodsProvider;
use Oro\Component\Layout\LayoutContext;
use Payever\Bundle\PaymentBundle\Constant\LanguageConstant;
use Payever\Bundle\PaymentBundle\Constant\SettingsConstant;
use Payever\Bundle\PaymentBundle\Method\Payever;
use Payever\Bundle\PaymentBundle\Service\Api\ServiceProvider;

/**
 * Class PaymentTermsProvider
 */
class PaymentTermsProvider
{
    const MODE_LIVE = 'production';
    const MODE_STAGE = 'stage';

    const LIVE_TERMS_JS = 'https://checkout.payever.org/terms.public.js';
    const STAGE_TERMS_JS = 'https://checkout.staging.devpayever.com/terms.public.js';

    /**
     * @var ConfigManager
     */
    private ConfigManager $configManager;

    /**
     * @var ServiceProvider
     */
    private ServiceProvider $serviceProvider;

    /**
     * @var ApplicablePaymentMethodsProvider
     */
    private ApplicablePaymentMethodsProvider $applicablePaymentMethodsProvider;

    /**
     * @var CheckoutPaymentContextProvider
     */
    private CheckoutPaymentContextProvider $checkoutPaymentContextProvider;

    /**
     * @var LocalizationHelper
     */
    private LocalizationHelper $localizationHelper;

    /**
     * @var array|null
     */
    private ?array $termsMethods = null;

    /**
     * @param ConfigManager $configManager
     * @param ServiceProvider $serviceProvider
     * @param ApplicablePaymentMethodsProvider $applicablePaymentMethodsProvider
     * @param CheckoutPaymentContextProvider $checkoutPaymentContextProvider
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(
        ConfigManager $configManager,
        ServiceProvider $serviceProvider,
        ApplicablePaymentMethodsProvider $applicablePaymentMethodsProvider,
        CheckoutPaymentContextProvider   $checkoutPaymentContextProvider,
        LocalizationHelper $localizationHelper,
    ) {
        $this->configManager = $configManager;
        $this->serviceProvider = $serviceProvider;
        $this->applicablePaymentMethodsProvider = $applicablePaymentMethodsProvider;
        $this->checkoutPaymentContextProvider = $checkoutPaymentContextProvider;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * Checks if the JS Widget must be added on the page.
     *
     * @param LayoutContext $context
     *
     * @return bool
     */
    public function isVisibleOnPayment(LayoutContext $context): bool
    {
        $methods = $this->getPaymentMethods($context);

        return count($methods) > 0;
    }

    /**
     * Get Settings for payment terms widget.
     *
     * @param LayoutContext $context
     *
     * @return array
     */
    public function getSettingsForTerms(LayoutContext $context): array
    {
        $methods = $this->getPaymentMethods($context);
        /** @var \Oro\Bundle\CheckoutBundle\Entity\Checkout $checkout */
        $checkout = $context->data()->get('checkout');

        $token = $this->getToken();
        $locale = $this->getLanguage();
        $env = $this->getEnvironment();

        $country = $checkout->getBillingAddress()->getCountry()->getIso2Code();

        $amount = $checkout->getSubtotals()->current()->getSubtotal()->getAmount();
        if ($checkout->getShippingCost()) {
            $amount += $checkout->getShippingCost()->getValue();
        }

        $paymentTerms = [
            'data' => [],
            'terms_js' => $env === self::MODE_LIVE ? self::LIVE_TERMS_JS : self::STAGE_TERMS_JS,
        ];

        foreach ($methods as $method) {
            $configs = $method->getConfigs();
            $paymentTerms['data'][] = [
                'env' => [
                    'locale' => $locale,
                    'environment' => $env ,
                ],
                'payment_key' => $configs['payment_method'],
                'payment' => [
                    'paymentMethod' => $configs['payment_method'],
                    'connectionId' => $configs['variant_id'],
                    'amount' => $amount,
                    'country' => $country,
                    'accessToken' => $token,
                    'styles' => [
                        'backgroundColor' => 'transparent',
                        'fontColor' => '#751d1d',
                        'buttonColor' => 'black',
                        'padding' => '1px 10px',
                    ],
                ]
            ];
        }

        return $paymentTerms;
    }

    /**
     * @param LayoutContext $context
     *
     * @return array
     */
    private function getPaymentMethods(LayoutContext $context)
    {
        if ($this->termsMethods === null) {
            try {
                $checkout = $context->data()->get('checkout');

                $context = $this->checkoutPaymentContextProvider->getContext($checkout);
                $methods = $this->applicablePaymentMethodsProvider->getApplicablePaymentMethods($context);

                $this->termsMethods = [];
                foreach ($methods as $method) {
                    if ($method instanceof Payever) {
                        $configs = $method->getConfigs();
                        if ($configs['payment_method'] === 'resurs_installment') {
                            $this->termsMethods[] = $method;
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->termsMethods = [];
            }
        }

        return $this->termsMethods;
    }

    /**
     * @return string
     */
    private function getToken()
    {
        try {
            return $this->serviceProvider
                ->getPaymentsApiClient()
                ->getToken()
                ->getAccessToken();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @return string
     */
    private function getEnvironment(): string
    {
        return  SettingsConstant::MODE_LIVE === $this->configManager->get('payever_payment.mode')
            ? self::MODE_LIVE
            : self::MODE_STAGE;
    }

    /**
     * @return string
     */
    private function getLanguage(): string
    {
        $checkoutLng = $this->configManager->get('payever_payment.checkout_language') ?: LanguageConstant::STORE;

        switch ($checkoutLng) {
            case LanguageConstant::NONE:
                return isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])
                    ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2)
                    : '';
            case LanguageConstant::STORE:
                $currentLocalization = $this->localizationHelper->getCurrentLocalization();

                return substr($currentLocalization->getLanguageCode(), 0, 2);
            default:
                return $checkoutLng;
        }
    }
}
