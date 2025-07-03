<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Action;

use Oro\Bundle\OrderBundle\Entity\Order;
use Payever\Bundle\PaymentBundle\Service\Helper\OrderItemHelper;
use Payever\Bundle\PaymentBundle\Service\Helper\CompareHelper;
use Payever\Bundle\PaymentBundle\Service\Management\PaymentActionManager;
use Payever\Sdk\Payments\Http\RequestEntity\PaymentItemEntity;
use Payever\Sdk\Core\Http\Response;
use Payever\Sdk\Payments\Http\ResponseEntity\RefundPaymentResponse;

class RefundAction extends ActionAbstract implements ActionInterface
{
    /**
     * @param Order $order
     * @param float|null $amount
     *
     * @return RefundPaymentResponse
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function execute(Order $order, ?float $amount): RefundPaymentResponse
    {
        $paymentId = $this->orderHelper->getPaymentId($order);
        if (!$paymentId) {
            throw new \LogicException('Payment transaction does not have stored payment id.');
        }

        // Amount't can\t be null: Amount should be a positive value
        if (is_null($amount)) {
            $amount = $this->orderManager->getAvailableRefundAmount($order);
        }

        $paymentAction = $this->paymentActionManager->addAction(
            $order,
            PaymentActionManager::ACTION_REFUND,
            PaymentActionManager::SOURCE_EXTERNAL,
            (float) $amount
        );

        try {
            /** @var Response $result */
            $result = $this->getPaymentApiClient()->refundPaymentRequest(
                $paymentId,
                round($amount, 2),
                $paymentAction->getIdentifier()
            );
        } catch (\Exception $exception) {
            $this->logger->critical('Refund action error: ' . $exception->getMessage());

            throw new \LogicException($exception->getMessage());
        }

        $this->logger->info(
            sprintf(
                'Refund action successfully executed for payment %s. Amount: %s',
                $paymentId,
                $amount
            )
        );

        /** @var RefundPaymentResponse $response */
        $response = $result->getResponseEntity();
        $this->logger->debug('Refund amount action response', $response->toArray());

        if (CompareHelper::areSame((float) $order->getTotal(), (float) $amount)) {
            $this->orderManager->refundOrderItems(
                $order,
                $this->orderManager->getOrderQtyAvailableForRefund($order)
            );

            return $response;
        }

        // Save refunded amount
        $this->orderManager->addRefundedAmount($order, $amount, true);

        return $response;
    }

    /**
     * @param Order $order
     * @param array $items
     *
     * @return RefundPaymentResponse
     * @throws \Exception
     */
    public function executeItems(Order $order, array $items): RefundPaymentResponse
    {
        $paymentId = $this->orderHelper->getPaymentId($order);
        if (!$paymentId) {
            throw new \LogicException('Payment transaction does not have stored payment id.');
        }

        $deliveryFee = 0;
        $paymentItems = [];
        foreach ($items as $itemReference => $qty) {
            if (!$qty) {
                continue;
            }

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
                ->setQuantity((int) $qty);

            $paymentItems[] = $paymentEntity;
        }

        if (empty($paymentItems) && $deliveryFee > 0) {
            return $this->execute($order, $deliveryFee);
        }

        $paymentAction = $this->paymentActionManager->addAction(
            $order,
            PaymentActionManager::ACTION_REFUND,
            PaymentActionManager::SOURCE_EXTERNAL,
            0
        );

        $result = $this->getPaymentApiClient()->refundItemsPaymentRequest(
            $paymentId,
            $paymentItems,
            $deliveryFee,
            $paymentAction->getIdentifier()
        );

        /** @var RefundPaymentResponse $response */
        $response = $result->getResponseEntity();
        $this->logger->debug('Refund items action response', $response->toArray());

        // Mark order items refunded
        $this->orderManager->refundOrderItems(
            $order,
            $items
        );

        return $response;
    }
}
