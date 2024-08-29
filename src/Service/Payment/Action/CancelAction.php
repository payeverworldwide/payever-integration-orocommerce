<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Action;

use Oro\Bundle\OrderBundle\Entity\Order;
use Payever\Bundle\PaymentBundle\Service\Helper\CompareHelper;
use Payever\Bundle\PaymentBundle\Service\Helper\OrderItemHelper;
use Payever\Bundle\PaymentBundle\Service\Management\PaymentActionManager;
use Payever\Sdk\Payments\Http\RequestEntity\PaymentItemEntity;
use Payever\Sdk\Payments\Http\ResponseEntity\CancelPaymentResponse;

class CancelAction extends ActionAbstract implements ActionInterface
{
    public function execute(Order $order, ?float $amount): CancelPaymentResponse
    {
        $paymentId = $this->orderHelper->getPaymentId($order);
        if (!$paymentId) {
            throw new \LogicException('Payment transaction does not have stored payment id.');
        }

        if ($amount) {
            $amount = round($amount, 2);
        }

        $paymentAction = $this->paymentActionManager->addAction(
            $order,
            PaymentActionManager::ACTION_CANCEL,
            PaymentActionManager::SOURCE_EXTERNAL,
            (float) $amount
        );

        try {
            /** @var CancelPaymentResponse $result */
            $result = $this->getPaymentApiClient()->cancelPaymentRequest(
                $paymentId,
                $amount,
                $paymentAction->getIdentifier()
            );
        } catch (\Exception $exception) {
            $this->logger->critical('Cancel action error: ' . $exception->getMessage());

            throw new \LogicException($exception->getMessage());
        }

        $this->logger->info(
            sprintf(
                'Cancel action successfully executed for payment %s. Amount: %s',
                $paymentId,
                $amount
            )
        );

        /** @var CancelPaymentResponse $response */
        $response = $result->getResponseEntity();
        $this->logger->debug('Cancel amount action response', $response->toArray());

        if (!$amount || CompareHelper::areSame((float) $order->getTotal(), (float) $amount)) {
            $this->orderManager->cancelOrderItems(
                $order,
                $this->orderManager->getOrderQtyAvailableForCancel($order)
            );

            return $response;
        }

        // Save cancelled amount
        $this->orderManager->addCancelledAmount($order, $amount, true);

        return $response;
    }

    public function executeItems(Order $order, array $items): CancelPaymentResponse
    {
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
            PaymentActionManager::ACTION_CANCEL,
            PaymentActionManager::SOURCE_EXTERNAL,
            0
        );

        $result = $this->getPaymentApiClient()->cancelItemsPaymentRequest(
            $paymentId,
            $paymentItems,
            $deliveryFee,
            $paymentAction->getIdentifier()
        );

        /** @var CancelPaymentResponse $response */
        $response = $result->getResponseEntity();
        $this->logger->debug('Cancel items response', $response->toArray());

        // Mark order items cancelled
        $this->orderManager->cancelOrderItems(
            $order,
            $items
        );

        return $response;
    }
}
