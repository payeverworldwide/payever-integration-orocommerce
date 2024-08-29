<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Controller\Frontend;

use Payever\Bundle\PaymentBundle\Service\Api\ServiceProvider;
use Payever\Bundle\PaymentBundle\Service\LogCollector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LogController extends AbstractController
{
    private const HEADER_AUTHORIZATION = 'Authorization';
    private const PARAMETER_TOKEN = 'token';

    /**
     * @Route("/download", name="payever_payment_zip_logs")
     */
    public function downloadAction(
        Request $request,
        ServiceProvider $serviceProvider,
        LogCollector $logCollector
    ): Response {
        try {
            $this->validateRequest($serviceProvider, $request);
            $logCollector->collect(false);
            $contents = $logCollector->getContents();
            $logCollector->remove();

            return $this->send($contents, $logCollector->getFileName());
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 400);
        }
    }

    /**
     * @Route("/shop", name="payever_payment_zip_shop_logs")
     *
     * @param Request $request
     * @param ServiceProvider $serviceProvider
     * @param LogCollector $logCollector
     * @return Response
     */
    public function shopAction(
        Request $request,
        ServiceProvider $serviceProvider,
        LogCollector $logCollector
    ): Response {
        try {
            $this->validateRequest($serviceProvider, $request);
            $logCollector->collect(true);
            $contents = $logCollector->getContents();
            $logCollector->remove();

            return $this->send($contents, $logCollector->getFileName());
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 400);
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            [
                ServiceProvider::class,
                LogCollector::class,
            ],
            parent::getSubscribedServices()
        );
    }

    /**
     * @param ServiceProvider $serviceProvider
     * @param Request $request
     * @return \Throwable|self
     * @throws \Exception
     */
    private function validateRequest(ServiceProvider $serviceProvider, Request $request): \Throwable|self
    {
        $accessToken = $request->headers->get(self::HEADER_AUTHORIZATION);
        if (!$accessToken) {
            $accessToken = $request->get(self::PARAMETER_TOKEN);
        }

        if (!$serviceProvider->isAccessTokenValid((string) $accessToken)) {
            throw new \Exception('Access token is invalid');
        }

        return $this;
    }

    /**
     * @param $contents
     * @param $fileName
     * @return Response
     */
    private function send($contents, $fileName): Response
    {
        return new Response(
            $contents,
            200,
            [
                'Content-Description' => 'File Transfer',
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename=' . $fileName,
                'Content-Transfer-Encoding' => 'binary'
            ]
        );
    }
}
