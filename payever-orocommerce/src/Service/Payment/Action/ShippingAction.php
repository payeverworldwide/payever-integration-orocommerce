<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Action;

use DateTime;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Payever\Bundle\PaymentBundle\Service\Api\ServiceProvider;
use Payever\Bundle\PaymentBundle\Service\Management\OrderManager;
use Payever\Bundle\PaymentBundle\Service\Helper\CompareHelper;
use Payever\Sdk\Payments\PaymentsApiClient;
use Payever\Sdk\Core\Base\ResponseInterface;
use Payever\Sdk\Core\Http\Response;
use Payever\Sdk\Payments\Http\RequestEntity\ShippingDetailsEntity;
use Payever\Sdk\Payments\Http\RequestEntity\ShippingGoodsPaymentRequest;
use Payever\Sdk\Payments\Http\ResponseEntity\ShippingGoodsPaymentResponse;
use Psr\Log\LoggerInterface;

class ShippingAction implements ActionInterface
{
    private ServiceProvider $serviceProvider;

    private OrderManager $orderManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct(
        ServiceProvider $serviceProvider,
        OrderManager $orderManager,
        LoggerInterface $logger
    ) {
        $this->serviceProvider = $serviceProvider;
        $this->orderManager = $orderManager;
        $this->logger = $logger;
    }

    public function execute(Order $order, string $paymentId, $amount = null): ShippingGoodsPaymentResponse
    {
        $shippingGoodsRequestEntity = new ShippingGoodsPaymentRequest();
        $shippingGoodsRequestEntity->setReason('Shipping');

        if ($amount) {
            $amount = floatval($amount);
            $shippingGoodsRequestEntity->setAmount(round($amount, 2));
        }

        $shippingDetailsEntity = new ShippingDetailsEntity();
        $shippingDetailsEntity->setShippingDate(
            $order->getCreatedAt()->format(DateTime::ISO8601)
        );

        $shippingGoodsRequestEntity->setShippingDetails($shippingDetailsEntity);

        try {
            /** @var Response $result */
            $result = $this->getPaymentApiClient()->shippingGoodsPaymentRequest(
                $paymentId,
                $shippingGoodsRequestEntity
            );
        } catch (\Exception $exception) {
            $this->logger->critical('Ship action error: ' . $exception->getMessage());

            throw new \LogicException($exception->getMessage());
        }

        $this->logger->info(
            sprintf(
                'Shipping goods action successfully executed for payment %s. Amount: %s',
                $paymentId,
                $amount
            )
        );

        /** @var ShippingGoodsPaymentResponse $response */
        $response = $result->getResponseEntity();
        if (!$amount || CompareHelper::areSame((float) $order->getTotal(), (float) $amount)) {
            $this->orderManager->shipOrderItems(
                $order,
                $this->orderManager->getOrderQtyAvailableForCapture($order)
            );

            return $response;
        }

        // Save captured amount
        $this->orderManager->addCapturedAmount($order, $amount, true);

        return $response;
    }

    /**
     * @return PaymentsApiClient
     */
    private function getPaymentApiClient(): PaymentsApiClient
    {
        return $this->serviceProvider->getPaymentsApiClient();
    }
}
