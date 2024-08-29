<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Payever\Bundle\PaymentBundle\Constant\SettingsConstant;
use Symfony\Contracts\Translation\TranslatorInterface;
use Payever\Bundle\PaymentBundle\Service\Helper\PaymentMethodHelper;
use Payever\Sdk\Payments\WidgetsApiClient;

class FinanceExpressConfig
{
    /**
     * @var WidgetsApiClient
     */
    private $widgetsApiClient;

    /**
     * @var PaymentMethodHelper
     */
    private PaymentMethodHelper $paymentMethodHelper;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        WidgetsApiClient $widgetsApiClient,
        PaymentMethodHelper $paymentMethodHelper,
        ConfigManager $configManager,
        TranslatorInterface $translator
    ) {
        $this->widgetsApiClient = $widgetsApiClient;
        $this->paymentMethodHelper = $paymentMethodHelper;
        $this->configManager = $configManager;
        $this->translator = $translator;
    }

    public function getWidgets(): array
    {
        try {
            $apiWidgets = $this->getApiPaymentWidgets();
        } catch (\Exception $exception) {
            $apiWidgets = [];
        }

        return array_merge(
            $this->getSavedPaymentWidgets(),
            $apiWidgets
        );
    }

    public function validateApi(): bool
    {
        try {
            $this->widgetsApiClient->getWidgets();
        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }

    public function setWidgetId(string $widgetId): void
    {
        $this->configManager->set('payever_payment.fe_widget_id', $widgetId);
        $this->configManager->flush();
    }

    /**
     * Get Widget ID.
     *
     * @return string|null
     */
    public function getWidgetId(): ?string
    {
        return $this->configManager->get('payever_payment.fe_widget_id');
    }

    /**
     * Get Widget ID.
     *
     * @return string
     */
    public function getWidgetTheme(): ?string
    {
        return $this->configManager->get('payever_payment.fe_widget_theme') ?? SettingsConstant::WIDGET_THEME_DARK;
    }

    /**
     * Get Checkout ID.
     *
     * @return string|null
     */
    public function getCheckoutId(): ?string
    {
        $widgetId = $this->getWidgetId();
        if ($widgetId) {
            $widgets = $this->getSavedPaymentWidgets();

            return isset($widgets[$widgetId]) ? $widgets[$widgetId]['checkout_id'] : null;
        }

        return null;
    }

    /**
     * Get Business ID.
     *
     * @return string|null
     */
    public function getBusinessId(): ?string
    {
        $widgetId = $this->getWidgetId();
        if ($widgetId) {
            $widgets = $this->getSavedPaymentWidgets();

            return isset($widgets[$widgetId]) ? $widgets[$widgetId]['business_id'] : null;
        }

        return null;
    }

    /* Get Widget Type.
     *
     * @return string|null
     */
    public function getWidgetType(): ?string
    {
        $widgetId = $this->getWidgetId();
        if ($widgetId) {
            $widgets = $this->getSavedPaymentWidgets();

            return isset($widgets[$widgetId]) ? $widgets[$widgetId]['type'] : null;
        }

        return null;
    }

    /**
     * Get Configured Payment Method.
     *
     * @return PaymentMethodInterface
     */
    public function getConfiguredPaymentMethod(): ?PaymentMethodInterface
    {
        $widgetId = $this->getWidgetId();
        $widgets = $this->getSavedPaymentWidgets();
        if (!$widgetId || count($widgets) === 0) {
            return null;
        }

        if (isset($widgets[$widgetId])) {
            foreach ($widgets[$widgetId]['payments'] as $paymentCode) {
                $method = $this->paymentMethodHelper->getPaymentMethod($paymentCode);
                if ($method) {
                    return $method;
                }
            }
        }

        return null;
    }

    public function isPaymentMethodConfigured(): bool
    {
        return null !== $this->getConfiguredPaymentMethod();
    }

    private function getApiPaymentWidgets(): array
    {
        $widgets = $this->widgetsApiClient->getWidgets()
            ->getResponseEntity()
            ->getResult();

        $result = [];
        foreach ($widgets as $widget) {
            if ($widget->getIsVisible()) {
                $widgetId = $widget->getId();
                $result[$widgetId] = [
                    'business_id' => $widget->getBusinessId(),
                    'checkout_id' => $widget->getCheckoutId(),
                    'type' => $widget->getType(),
                    'payments' => [],
                ];

                foreach ($widget->getPayments() as $payment) {
                    if ($payment->getEnabled()) {
                        $result[$widgetId]['payments'][] = $payment->getPaymentMethod();
                    }
                }

                $result[$widgetId]['payments'] = array_unique($result[$widgetId]['payments']);

                // Get payments translations
                $payments = [];
                foreach ($result[$widgetId]['payments'] as $payment) {
                    $payments[] = $this->translate($payment);
                }

                // Make title
                $result[$widgetId]['title'] =
                    sprintf(
                        '%s - %s',
                        $this->translate($widget['type']),
                        implode(', ', $payments)
                    );
            }
        }

        $this->setSavedPaymentWidgets($result);

        return $result;
    }

    private function getSavedPaymentWidgets(): array
    {
        $data = $this->configManager->get('payever_payment.fe_payment_widgets');
        if (empty($data)) {
            return [];
        }

        $data = json_decode($data, true);
        if (JSON_ERROR_NONE === json_last_error()) {
            return $data;
        }

        return [];
    }

    private function setSavedPaymentWidgets(array $widgets): void
    {
        $this->configManager->set('payever_payment.fe_payment_widgets', json_encode($widgets));
        $this->configManager->flush();
    }

    private function translate(string $text): string
    {
        return $this->translator->trans('payever.system_configuration.fields.fe_configuration.choices.' . trim($text));
    }
}
