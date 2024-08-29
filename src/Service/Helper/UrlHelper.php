<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Helper;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Payever\Bundle\PaymentBundle\Constant\QueryConstant;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class UrlHelper
{
    private const ROUTE_PAYMENT_IFRAME = 'payever_payment_payment';

    /**
     * @var RouterInterface
     */
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function getSuccessUrl(PaymentTransaction $paymentTransaction): string
    {
        return $this->router->generate(
            'oro_payment_callback_return',
            [
                QueryConstant::PARAMETER_ACCESS_ID => $paymentTransaction->getAccessIdentifier(),
                QueryConstant::PARAMETER_PAYMENT_ID => QueryConstant::PAYMENT_ID_PLACEHODLER,
                QueryConstant::PARAMETER_TYPE => QueryConstant::CALLBACK_TYPE_SUCCESS
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    public function getFailureUrl(PaymentTransaction $paymentTransaction): string
    {
        return $this->router->generate(
            'oro_payment_callback_error',
            [
                QueryConstant::PARAMETER_ACCESS_ID => $paymentTransaction->getAccessIdentifier(),
                QueryConstant::PARAMETER_PAYMENT_ID => QueryConstant::PAYMENT_ID_PLACEHODLER,
                QueryConstant::PARAMETER_TYPE => QueryConstant::CALLBACK_TYPE_FAILURE
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    public function getCancelUrl(PaymentTransaction $paymentTransaction): string
    {
        return $this->router->generate(
            'oro_payment_callback_error',
            [
                QueryConstant::PARAMETER_ACCESS_ID => $paymentTransaction->getAccessIdentifier(),
                QueryConstant::PARAMETER_PAYMENT_ID => QueryConstant::PAYMENT_ID_PLACEHODLER,
                QueryConstant::PARAMETER_TYPE => QueryConstant::CALLBACK_TYPE_CANCEL
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    public function getNoticeUrl(PaymentTransaction $paymentTransaction): string
    {
        return $this->router->generate(
            'oro_payment_callback_notify',
            [
                QueryConstant::PARAMETER_ACCESS_ID => $paymentTransaction->getAccessIdentifier(),
                QueryConstant::PARAMETER_ACCESS_TOKEN => $paymentTransaction->getAccessToken(),
                QueryConstant::PARAMETER_PAYMENT_ID => QueryConstant::PAYMENT_ID_PLACEHODLER
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    public function getPendingUrl(PaymentTransaction $paymentTransaction): string
    {
        return $this->router->generate(
            'oro_payment_callback_return',
            [
                QueryConstant::PARAMETER_ACCESS_ID => $paymentTransaction->getAccessIdentifier(),
                QueryConstant::PARAMETER_PAYMENT_ID => QueryConstant::PAYMENT_ID_PLACEHODLER,
                QueryConstant::PARAMETER_TYPE => QueryConstant::CALLBACK_TYPE_PENDING
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @param string $returnUrl
     * @return string
     */
    public function generateIframeUrl(string $returnUrl): string
    {
        return $this->router->generate(
            self::ROUTE_PAYMENT_IFRAME,
            ['returnUrl' => $returnUrl],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
