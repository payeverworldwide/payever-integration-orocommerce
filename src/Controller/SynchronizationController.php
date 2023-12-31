<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Controller;

use Payever\Bundle\PaymentBundle\Service\Api\ServiceProvider;
use Payever\Bundle\PaymentBundle\Service\Payment\PaymentOptionsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class SynchronizationController extends AbstractController
{
    /**
     * @Route("/synchronize", name="payever_payment_synchronize")
     */
    public function synchronizeAction(
        Request $request,
        TranslatorInterface $translator,
        ServiceProvider $serviceProvider,
        PaymentOptionsService $paymentOptionsService
    ): JsonResponse {
        $clientId = $request->get('clientId');
        $clientSecret = $request->get('clientSecret');
        $businessUuid = $request->get('businessUuid');
        $mode = $request->get('mode');

        try {
            // Save credentials
            $serviceProvider->setApiCredentials($clientId, $clientSecret, $businessUuid, $mode);

            // Start synchronization
            $paymentOptionsService->synchronizePaymentOptions();
        } catch (\Exception $exception) {
            return new JsonResponse(
                [
                    'error' => true,
                    'message' => $translator->trans('payever.admin.synchronization.error') . ' ' . $exception->getMessage() //phpcs:ignore
                ],
                200
            );
        }

        return new JsonResponse(
            [
                'success' => true,
                'message' => $translator->trans('payever.admin.synchronization.success')
            ],
            200
        );
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            [
                ServiceProvider::class,
                TranslatorInterface::class,
                PaymentOptionsService::class,
            ],
            parent::getSubscribedServices()
        );
    }
}
