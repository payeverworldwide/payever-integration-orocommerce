<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Controller\Frontend;

use Payever\Bundle\PaymentBundle\Service\Payment\Notification\NotificationRequestProcessor;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    /**
     * @Route("/", name="payever_notification_index", methods={"POST"})
     *
     * @param Request $request
     * @param NotificationRequestProcessor $notificationRequestProcessor
     *
     * @return JsonResponse
     */
    public function indexAction(
        Request $request,
        NotificationRequestProcessor $notificationRequestProcessor
    ): JsonResponse {
        $this->getLogger()->debug(__METHOD__);

        try {
            $this->getLogger()->info('[Notification] Notice callback hit for payment');
            $this->getLogger()->info('[Notification] Payload: ' . $request->getContent());
            $response = $notificationRequestProcessor->processNotification();

            return new JsonResponse(['result' => 'success', 'message' => $response->__toString()]);
        } catch (\Exception $exception) {
            $this->getLogger()->error('No payment method found onNotify event');

            return new JsonResponse(['result' => 'error', 'message' => $exception->getMessage()]);
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            LoggerInterface::class,
        ]);
    }

    /**
     * @return LoggerInterface
     */
    private function getLogger(): LoggerInterface
    {
        return $this->container->get(LoggerInterface::class);
    }
}
