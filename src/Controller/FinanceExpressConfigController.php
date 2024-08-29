<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Controller;

use Payever\Bundle\PaymentBundle\Service\FinanceExpressConfig;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class FinanceExpressConfigController extends AbstractController
{
    /**
     * @Route("/get_widgets", name="payever_payment_get_finance_express_widgets")
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getWidgetsAction(
        Request $request,
        TranslatorInterface $translator,
        FinanceExpressConfig $financeExpressConfig
    ): JsonResponse {
        if (!$financeExpressConfig->validateApi()) {
            return new JsonResponse(
                [
                    'success' => false,
                    'message' => $translator->trans('payever.admin.fe_widgets.invalid_credentials')
                ]
            );
        }

        $widgets = $financeExpressConfig->getWidgets();
        if (count($widgets) === 0) {
            return new JsonResponse(
                [
                    'success' => false,
                    'message' => $translator->trans('payever.admin.fe_widgets.widgets_missing')
                ]
            );
        }

        $result = [];
        foreach ($widgets as $widgetId => $widget) {
            $result[] = [
                'id' => $widgetId,
                'text' => $widget['title']
            ];
        }

        $widgetId = $financeExpressConfig->getWidgetId();

        return new JsonResponse(
            [
                'success' => true,
                'result' => $result,
                'widgets' => $widgets,
                'widget_id' => $widgetId,
                'widget_title' => isset($widgets[$widgetId]) ? $widgets[$widgetId]['title'] : ''
            ]
        );
    }

    /**
     * @Route("/save_widget", name="payever_payment_save_widget")
     */
    public function saveWidgetId(
        Request $request,
        FinanceExpressConfig $financeExpressConfig
    ): JsonResponse {
        $widgetId = $request->get('widgetId');
        if ($widgetId) {
            $financeExpressConfig->setWidgetId($widgetId);
        }

        return new JsonResponse(
            [
                'success' => true
            ],
            200
        );
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            [
                TranslatorInterface::class,
                FinanceExpressConfig::class
            ],
            parent::getSubscribedServices()
        );
    }
}
