<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Helper;

use Payever\Bundle\PaymentBundle\Constant\QueryConstant;
use Payever\Sdk\Payments\Enum\Status;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class UrlHelper
{
    const ROUTE_PAYEVER_PAYMENT_IFRAME = 'payever_payment_iframe';
    const ROUTE_PAYEVER_CALLBACK_SUCCESS = 'payever_callback_success';
    const ROUTE_PAYEVER_CALLBACK_PENDING = 'payever_callback_pending';
    const ROUTE_PAYEVER_CALLBACK_CANCEL = 'payever_callback_cancel';
    const ROUTE_PAYEVER_CALLBACK_FAILURE = 'payever_callback_failure';
    const ROUTE_PAYEVER_CALLBACK_STATUS_UPDATE = 'payever_callback_status_update';
    const ROUTE_PAYEVER_NOTIFICATION = 'payever_notification_index';

    /**
     * @var RouterInterface
     */
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param int $checkoutId
     * @param array $params
     *
     * @return string
     */
    public function getSuccessUrl(int $checkoutId, array $params = []): string
    {
        return $this->router->generate(
            self::ROUTE_PAYEVER_CALLBACK_SUCCESS,
            array_merge(
                [
                    QueryConstant::PARAMETER_CHECKOUT_ID => $checkoutId,
                    QueryConstant::PARAMETER_PAYMENT_ID => QueryConstant::PAYMENT_ID_PLACEHODLER,
                ],
                $params
            ),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @param int $checkoutId
     * @param array $params
     *
     * @return string
     */
    public function getPendingUrl(int $checkoutId, array $params = []): string
    {
        return $this->router->generate(
            self::ROUTE_PAYEVER_CALLBACK_PENDING,
            array_merge(
                [
                    QueryConstant::PARAMETER_CHECKOUT_ID => $checkoutId,
                    QueryConstant::PARAMETER_PAYMENT_ID => QueryConstant::PAYMENT_ID_PLACEHODLER,
                ],
                $params
            ),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @param int $checkoutId
     * @param array $params
     *
     * @return string
     */
    public function getFailureUrl(int $checkoutId, array $params = []): string
    {
        return $this->router->generate(
            self::ROUTE_PAYEVER_CALLBACK_FAILURE,
            array_merge(
                [
                    QueryConstant::PARAMETER_CHECKOUT_ID => $checkoutId,
                    QueryConstant::PARAMETER_PAYMENT_ID => QueryConstant::PAYMENT_ID_PLACEHODLER,
                ],
                $params
            ),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @param int $checkoutId
     * @param array $params
     *
     * @return string
     */
    public function getCancelUrl(int $checkoutId, array $params = []): string
    {
        return $this->router->generate(
            self::ROUTE_PAYEVER_CALLBACK_CANCEL,
            array_merge(
                [
                    QueryConstant::PARAMETER_CHECKOUT_ID => $checkoutId,
                    QueryConstant::PARAMETER_PAYMENT_ID => QueryConstant::PAYMENT_ID_PLACEHODLER,
                ],
                $params
            ),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getNoticeUrl(array $params = []): string
    {
        return $this->router->generate(
            self::ROUTE_PAYEVER_NOTIFICATION,
            array_merge(
                [
                    QueryConstant::PARAMETER_PAYMENT_ID => QueryConstant::PAYMENT_ID_PLACEHODLER,
                ],
                $params
            ),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @param string $accessIdentifier
     * @param string $paymentId
     *
     * @return string
     */
    public function getPurchaseReturnUrl(string $accessIdentifier, string $paymentId): string
    {
        return $this->router->generate(
            'oro_payment_callback_return',
            [
                QueryConstant::PARAMETER_ACCESS_ID => $accessIdentifier,
                QueryConstant::PARAMETER_PAYMENT_ID => $paymentId,
                QueryConstant::PARAMETER_TYPE => QueryConstant::CALLBACK_TYPE_SUCCESS
            ]
        );
    }

    /**
     * @param string $returnUrl
     *
     * @return string
     */
    public function generateIframeUrl(string $returnUrl): string
    {
        return $this->router->generate(
            self::ROUTE_PAYEVER_PAYMENT_IFRAME,
            [QueryConstant::PARAM_PAYMENT_URL => $returnUrl],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @param int $checkoutId
     * @param string $paymentId
     *
     * @return string
     */
    public function generateStatusUpdateUrl(int $checkoutId, string $paymentId): string
    {
        return $this->router->generate(
            self::ROUTE_PAYEVER_CALLBACK_STATUS_UPDATE,
            [
                QueryConstant::PARAMETER_PAYMENT_ID => $paymentId,
                QueryConstant::PARAMETER_CHECKOUT_ID => $checkoutId,
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @param string $status
     * @param int $checkoutId
     * @param string $paymentId
     *
     * @return string
     */
    public function getCallbackUrl(string $status, int $checkoutId, string $paymentId): string
    {
        return match ($status) {
            Status::STATUS_ACCEPTED, Status::STATUS_PAID => $this->getSuccessUrl(
                $checkoutId,
                [QueryConstant::PARAMETER_PAYMENT_ID => $paymentId]
            ),
            Status::STATUS_FAILED, Status::STATUS_DECLINED => $this->getFailureUrl(
                $checkoutId,
                [QueryConstant::PARAMETER_PAYMENT_ID => $paymentId]
            ),
            Status::STATUS_CANCELLED => $this->getCancelUrl(
                $checkoutId,
                [QueryConstant::PARAMETER_PAYMENT_ID => $paymentId]
            ),
            default => '',
        };
    }
}
