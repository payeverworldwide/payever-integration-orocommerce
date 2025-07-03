<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Controller\Frontend;

use Exception;
use Psr\Log\LoggerInterface;
use Payever\Bundle\PaymentBundle\Attribute\Layout;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Payever\Bundle\PaymentBundle\Constant\QueryConstant;
use Payever\Sdk\Payments\Http\MessageEntity\RetrievePaymentResultEntity;
use Payever\Sdk\Payments\Enum\Status;
use Payever\Bundle\PaymentBundle\Service\Api\ServiceProvider;

class PaymentController extends AbstractController
{
    /**
     * @var ServiceProvider
     */
    private ServiceProvider $serviceProvider;

    private const PARAM_RETURN_URL = 'returnUrl';
    const ORO_PAYMENT_CALLBACK_RETURN = 'oro_payment_callback_return';
    const ORO_PAYMENT_CALLBACK_ERROR = 'oro_payment_callback_error';
    const PAYEVER_PAYMENT_STATUS_UPDATE = 'payever_payment_status_update';
    const PAYEVER_PAYMENT_PENDING = 'payever_payment_pending';

    /**
     * @Route("/payment", name="payever_payment_payment")
     * @return array|Response
     */
    #[Layout(vars: ['iframeUrl'])]
    public function paymentAction(Request $request)
    {
        return [
            'iframeUrl' => $request->query->get(self::PARAM_RETURN_URL)
        ];
    }

    /**
     * @Route("/pending", name="payever_payment_pending")
     * @return array|Response
     */
    #[Layout(vars: ['api_order_update_status', 'is_loan_transaction'])]
    public function pendingAction(Request $request)
    {
        $paymentId = $request->get(QueryConstant::PARAMETER_PAYMENT_ID);
        $accessIdentifier = $request->get(QueryConstant::PARAMETER_ACCESS_ID);

        return [
            'is_loan_transaction' => $request->get(QueryConstant::PARAMETER_IS_LOAD_TRANSACTION),
            'api_order_update_status' => $this->getRouter()->generate(
                self::PAYEVER_PAYMENT_STATUS_UPDATE,
                [
                    QueryConstant::PARAMETER_PAYMENT_ID => $paymentId,
                    QueryConstant::PARAMETER_ACCESS_ID => $accessIdentifier
                ]
            )
        ];
    }

    /**
     * @Route("/statusupdate", name="payever_payment_status_update")
     * @return JsonResponse
     */
    public function statusUpdateAction(Request $request)
    {
        $paymentId = $request->get(QueryConstant::PARAMETER_PAYMENT_ID);
        $accessIdentifier = $request->get(QueryConstant::PARAMETER_ACCESS_ID);

        try {
            if (!$paymentId || !$accessIdentifier) {
                throw new \InvalidArgumentException('Invalid payload parameter.');
            }

            /** @var RetrievePaymentResultEntity $payeverPayment */
            $payeverPayment = $this->serviceProvider
                ->getPaymentsApiClient()
                ->retrievePaymentRequest($paymentId)
                ->getResponseEntity()
                ->getResult();

            $redirectUrl = '';
            $responseStatus = $payeverPayment->getStatus();

            switch ($responseStatus) {
                case Status::STATUS_ACCEPTED:
                case Status::STATUS_PAID:
                    $redirectUrl = $this->getUrl(QueryConstant::CALLBACK_TYPE_SUCCESS, $accessIdentifier, $paymentId);
                    break;
                case Status::STATUS_FAILED:
                case Status::STATUS_DECLINED:
                    $redirectUrl = $this->getUrl(QueryConstant::CALLBACK_TYPE_FAILURE, $accessIdentifier, $paymentId);
                    break;
                case Status::STATUS_CANCELLED:
                    $redirectUrl = $this->getUrl(QueryConstant::CALLBACK_TYPE_CANCEL, $accessIdentifier, $paymentId);
                    break;
            }

            return new JsonResponse(['result' => 'success', 'url' => $redirectUrl]);
        } catch (\Exception $e) {
            return new JsonResponse(['result' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                LoggerInterface::class
            ]
        );
    }

    private function getUrl($type, $accessIdentifier, $paymentId): string
    {
        switch ($type) {
            case QueryConstant::CALLBACK_TYPE_SUCCESS:
                $oroPaymentCallback = self::ORO_PAYMENT_CALLBACK_RETURN;
                break;
            case QueryConstant::CALLBACK_TYPE_FAILURE:
            case QueryConstant::CALLBACK_TYPE_CANCEL:
                $oroPaymentCallback = self::ORO_PAYMENT_CALLBACK_ERROR;
                break;
        }
        return $this->getRouter()->generate(
            $oroPaymentCallback,
            [
                QueryConstant::PARAMETER_ACCESS_ID => $accessIdentifier,
                QueryConstant::PARAMETER_PAYMENT_ID => $paymentId,
                QueryConstant::PARAMETER_TYPE => $type
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    public function setServiceProvider(ServiceProvider $serviceProvider)
    {
        $this->serviceProvider = $serviceProvider;
    }

    private function getRouter(): RouterInterface
    {
        return $this->container->get('router');
    }
}
