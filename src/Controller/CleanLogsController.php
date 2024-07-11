<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Controller;

use Payever\Bundle\PaymentBundle\Service\LogCollector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class CleanLogsController extends AbstractController
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/clean_logs', name: 'payever_payment_clean_logs')]
    public function synchronizeAction(
        TranslatorInterface $translator,
        LogCollector $logCollector
    ): JsonResponse {
        $logCollector->cleanLogs();

        return new JsonResponse(
            [
                'success' => true,
                'message' => $translator->trans('payever.admin.clean_logs.success')
            ],
            \Symfony\Component\HttpFoundation\Response::HTTP_OK
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
