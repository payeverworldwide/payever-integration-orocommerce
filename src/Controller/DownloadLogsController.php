<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Controller;

use Payever\Bundle\PaymentBundle\Service\LogCollector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DownloadLogsController extends AbstractController
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/download_logs', name: 'payever_payment_download_logs')]
    public function synchronizeAction(
        LogCollector $logCollector
    ): Response {
        $logCollector->collect();
        $contents = $logCollector->getContents();
        $logCollector->remove();

        return new Response(
            $contents,
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            [
                'Content-Description' => 'File Transfer',
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename=payever_logs.zip',
                'Content-Transfer-Encoding' => 'binary'
            ]
        );
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            [
                LogCollector::class
            ],
            parent::getSubscribedServices()
        );
    }
}
