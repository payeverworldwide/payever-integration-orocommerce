<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Controller;

use Payever\Bundle\PaymentBundle\Service\LogCollector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DownloadLogsController extends AbstractController
{
    /**
     * @Route("/download_logs", name="payever_payment_download_logs")
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function synchronizeAction(
        Request $request,
        LogCollector $logCollector
    ): Response {
        $logCollector->collect(true);
        $contents = $logCollector->getContents();
        $logCollector->remove();

        return new Response(
            $contents,
            200,
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
