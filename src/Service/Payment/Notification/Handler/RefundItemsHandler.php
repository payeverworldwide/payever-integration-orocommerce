<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Notification\Handler;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Payever\Bundle\PaymentBundle\Service\Helper\OrderItemHelper;
use Payever\Sdk\Payments\Enum\Status;
use Payever\Sdk\Payments\Notification\MessageEntity\NotificationResultEntity;
use Payever\Sdk\Payments\Notification\NotificationRequestProcessor;

class RefundItemsHandler extends NotificationHandlerAbstract implements HandlerInterface
{
    /**
     * @iheritdoc
     */
    public function execute(NotificationResultEntity $notificationResultEntity): array
    {
        $orderReference = $notificationResultEntity->getReference();
        $order = $this->transactionHelper->getOrderByIdentifier($orderReference);
        if (!$order) {
            throw new \UnexpectedValueException('Order is not found');
        }

        $paymentId = $notificationResultEntity->getId();
        $this->logger->info(sprintf(
            '%s Handle refund items action. Order ID: %s. Payment ID: %s',
            NotificationRequestProcessor::LOG_PREFIX,
            $order->getId(),
            $paymentId
        ));

        $refundedItems = $notificationResultEntity->getRefundedItems();
        $refundAmount = $notificationResultEntity->getRefundAmount();
        $totalRefundedAmount = (float) $notificationResultEntity->getTotalRefundedAmount();

        // Check if transaction has handled before
        if ($this->isPaymentTransactionExists($order, PaymentMethodInterface::REFUND, $refundAmount)) {
            $this->logger->warning(
                sprintf(
                    '%s Payment action was rejected because it was registered before.',
                    NotificationRequestProcessor::LOG_PREFIX
                )
            );

            return [
                'successful' => true,
            ];
        }

        // Mark order items refunded
        $amount = 0;
        foreach ($refundedItems as $item) {
            $orderItem = $this->orderManager->getOrderItem($order, $item['identifier']);
            if (!$orderItem) {
                $this->logger->error(
                    NotificationRequestProcessor::LOG_PREFIX . ' Item is not found: ' . $item['identifier']
                );

                continue;
            }

            $orderItem->setQtyRefunded($item[self::ITEM_QTY]);
            $this->entityManager->persist($orderItem);
            $this->entityManager->flush($orderItem);

            $amount += $item[self::ITEM_QTY] * $item[self::ITEM_PRICE];
        }

        // Mark shipping refunded if applicable
        if ($refundAmount > $amount) {
            $orderItem = $this->orderManager->getOrderItemByType($order, OrderItemHelper::TYPE_SHIPPING);
            if ($orderItem) {
                $orderItem->setQtyRefunded(1);

                $this->entityManager->persist($orderItem);
                $this->entityManager->flush($orderItem);
            }
        }

        // Update total captured
        $total = $this->orderManager->getOrderTotal($order);
        if ($total) {
            $total->setRefundedTotal($totalRefundedAmount);

            $this->entityManager->persist($total);
            $this->entityManager->flush($total);
        }

        $paymentTransaction = $this->transactionBuilder->registerRefundTransaction(
            $order,
            $paymentId,
            $refundAmount,
            $notificationResultEntity->toArray()
        );
        $this->logger->info('Transaction has been registered', [$paymentTransaction->getId()]);

        $this->logger->info(
            sprintf(
                '%s Refunded items. Transaction amount: %s. Items: %s',
                NotificationRequestProcessor::LOG_PREFIX,
                $refundAmount,
                json_encode($refundedItems)
            )
        );

        return [
            'successful' => true,
        ];
    }

    /**
     * @iheritdoc
     */
    public function isApplicable(NotificationResultEntity $notificationResultEntity): bool
    {
        $status = $notificationResultEntity->getStatus();
        $refundedItems = $notificationResultEntity->getRefundedItems();
        $captureAmount = $notificationResultEntity->getCaptureAmount();
        $cancelAmount = $notificationResultEntity->getCancelAmount();

        return in_array($status, [Status::STATUS_REFUNDED, Status::STATUS_CANCELLED]) &&
            (!$captureAmount && !$cancelAmount) &&
            ($refundedItems && count($refundedItems) > 0);
    }
}
