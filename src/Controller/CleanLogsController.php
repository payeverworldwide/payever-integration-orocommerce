<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Controller;

use Payever\Bundle\PaymentBundle\Service\LogCollector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class CleanLogsController extends AbstractController
{
    /**
     * @Route("/clean_logs", name="payever_payment_clean_logs")
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function cleanAction(
        Request $request,
        TranslatorInterface $translator,
        LogCollector $logCollector
    ): JsonResponse {
        $logCollector->cleanLogs();

        return new JsonResponse(
            [
                'success' => true,
                'message' => $translator->trans('payever.admin.clean_logs.success')
            ],
            200
        );
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            [
                TranslatorInterface::class,
                LogCollector::class
            ],
            parent::getSubscribedServices()
        );
    }
}
