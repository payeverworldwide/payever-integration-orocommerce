<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Action;

use DateTime;
use Oro\Bundle\OrderBundle\Entity\Order;
use Payever\Bundle\PaymentBundle\Service\Helper\CompareHelper;
use Payever\Bundle\PaymentBundle\Service\Helper\OrderItemHelper;
use Payever\Bundle\PaymentBundle\Service\Management\PaymentActionManager;
use Payever\Sdk\Core\Http\Response;
use Payever\Sdk\Payments\Http\RequestEntity\PaymentItemEntity;
use Payever\Sdk\Payments\Http\RequestEntity\ShippingDetailsEntity;
use Payever\Sdk\Payments\Http\RequestEntity\ShippingGoodsPaymentRequest;
use Payever\Sdk\Payments\Http\ResponseEntity\ShippingGoodsPaymentResponse;

class ShippingAction extends ActionAbstract implements ActionInterface
{
    /**
     * @param Order $order
     * @param $amount
     *
     * @return ShippingGoodsPaymentResponse
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function execute(Order $order, ?float $amount): ShippingGoodsPaymentResponse
    {
        $paymentId = $this->orderHelper->getPaymentId($order);
        if (!$paymentId) {
            throw new \LogicException('Payment transaction does not have stored payment id.');
        }

        $shippingGoodsRequestEntity = new ShippingGoodsPaymentRequest();
        $shippingGoodsRequestEntity->setReason('Shipping');

        if ($amount) {
            $shippingGoodsRequestEntity->setAmount(round($amount, 2));
        }

        $paymentAction = $this->paymentActionManager->addAction(
            $order,
            PaymentActionManager::ACTION_SHIPPING_GOODS,
            PaymentActionManager::SOURCE_EXTERNAL,
            (float) $amount
        );

        $shippingDetailsEntity = new ShippingDetailsEntity();
        $shippingDetailsEntity->setShippingDate(
            $order->getCreatedAt()->format(DateTime::ISO8601)
        );

        $shippingGoodsRequestEntity->setShippingDetails($shippingDetailsEntity);

        try {
            /** @var Response $result */
            $result = $this->getPaymentApiClient()->shippingGoodsPaymentRequest(
                $paymentId,
                $shippingGoodsRequestEntity,
                $paymentAction->getIdentifier()
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
        $this->logger->debug('Shipping amount action response', $response->toArray());
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

    public function executeItems(Order $order, array $items): ShippingGoodsPaymentResponse
    {
        return $this->executeItemsWithDetails($order, $items, null, null, null);
    }

    public function executeItemsWithDetails(
        Order $order,
        array $items,
        ?string $trackingNumber,
        ?string $trackingUrl,
        ?string $shippingDate
    ): ShippingGoodsPaymentResponse {
        $paymentId = $this->orderHelper->getPaymentId($order);
        if (!$paymentId) {
            throw new \LogicException('Payment transaction does not have stored payment id.');
        }

        $deliveryFee = 0;
        $paymentItems = [];
        foreach ($items as $itemReference => $qty) {
            $orderItem = $this->orderManager->getOrderItem($order, $itemReference);
            if (!$orderItem) {
                $this->logger->critical(sprintf('Order item %s does not exists.', $itemReference));

                continue;
            }

            if ($orderItem->getItemType() === OrderItemHelper::TYPE_SHIPPING) {
                $deliveryFee = $qty > 0 ? $orderItem->getUnitPrice() : 0;

                continue;
            }

            $paymentEntity = new PaymentItemEntity();
            $paymentEntity->setIdentifier($orderItem->getItemReference())
                ->setName($orderItem->getName())
                ->setPrice(round((float) $orderItem->getUnitPrice(), 2))
                ->setQuantity($qty);

            $paymentItems[] = $paymentEntity;
        }

        $paymentAction = $this->paymentActionManager->addAction(
            $order,
            PaymentActionManager::ACTION_SHIPPING_GOODS,
            PaymentActionManager::SOURCE_EXTERNAL,
            0
        );

        $shippingDetailsEntity = new ShippingDetailsEntity();
        $shippingDetailsEntity->setShippingDate(
            $shippingDate ? (new DateTime($shippingDate))->format(DateTime::ISO8601) :
                $order->getCreatedAt()->format(DateTime::ISO8601)
        )
            ->setShippingMethod($order->getShippingMethod())
            ->setTrackingNumber($trackingNumber)
            ->setTrackingUrl($trackingUrl);

        $shippingGoodsRequestEntity = new ShippingGoodsPaymentRequest();
        $shippingGoodsRequestEntity->setReason('Shipping')
            ->setPaymentItems($paymentItems)
            ->setDeliveryFee($deliveryFee)
            ->setShippingDetails($shippingDetailsEntity);

        $result = $this->getPaymentApiClient()->shippingGoodsPaymentRequest(
            $paymentId,
            $shippingGoodsRequestEntity,
            $paymentAction->getIdentifier()
        );

        /** @var ShippingGoodsPaymentResponse $response */
        $response = $result->getResponseEntity();
        $this->logger->debug('Shipping items action response', $response->toArray());

        // Mark order items captured
        $this->orderManager->shipOrderItems(
            $order,
            $items
        );

        return $response;
    }
}
