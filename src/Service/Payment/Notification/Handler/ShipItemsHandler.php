<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Notification\Handler;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Payever\Bundle\PaymentBundle\Service\Helper\OrderItemHelper;
use Payever\Sdk\Payments\Notification\MessageEntity\NotificationResultEntity;
use Payever\Sdk\Payments\Notification\NotificationRequestProcessor;

class ShipItemsHandler extends NotificationHandlerAbstract implements HandlerInterface
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
            '%s Handle shipping items action. Order ID: %s. Payment ID: %s',
            NotificationRequestProcessor::LOG_PREFIX,
            $order->getId(),
            $paymentId
        ));

        $capturedItems = $notificationResultEntity->getCapturedItems();
        $captureAmount = $notificationResultEntity->getCaptureAmount();
        $totalCapturedAmount = (float) $notificationResultEntity->getTotalCapturedAmount();

        // Check if transaction has handled before
        if ($this->isPaymentTransactionExists($order, PaymentMethodInterface::CAPTURE, $captureAmount)) {
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

        // Mark order items shipped
        $amount = 0;
        foreach ($capturedItems as $item) {
            $orderItem = $this->orderManager->getOrderItem($order, $item['identifier']);
            if (!$orderItem) {
                $this->logger->error(
                    NotificationRequestProcessor::LOG_PREFIX . ' Item is not found: ' . $item['identifier']
                );

                continue;
            }

            $orderItem->setQtyCaptured($item[self::ITEM_QTY]);
            $this->entityManager->persist($orderItem);
            $this->entityManager->flush($orderItem);

            $amount += $item[self::ITEM_QTY] * $item[self::ITEM_PRICE];
        }

        // Mark shipping shipped if applicable
        if ($captureAmount > $amount) {
            $orderItem = $this->orderManager->getOrderItemByType($order, OrderItemHelper::TYPE_SHIPPING);
            if ($orderItem) {
                $orderItem->setQtyCaptured(1);

                $this->entityManager->persist($orderItem);
                $this->entityManager->flush($orderItem);
            }
        }

        // Update total captured
        $total = $this->orderManager->getOrderTotal($order);
        if ($total) {
            $total->setCapturedTotal($totalCapturedAmount);

            $this->entityManager->persist($total);
            $this->entityManager->flush($total);
        }

        $paymentTransaction = $this->transactionBuilder->registerCaptureTransaction(
            $order,
            $paymentId,
            $captureAmount,
            $notificationResultEntity->toArray()
        );
        $this->logger->info('Transaction has been registered', [$paymentTransaction->getId()]);

        $this->logger->info(
            sprintf(
                NotificationRequestProcessor::LOG_PREFIX . ' Captured items. Transaction amount: %s. Items: %s',
                $captureAmount,
                json_encode($capturedItems)
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
        $capturedItems = $notificationResultEntity->getCapturedItems();
        $refundAmount = $notificationResultEntity->getRefundAmount();
        $cancelAmount = $notificationResultEntity->getCancelAmount();

        return (!$refundAmount && !$cancelAmount) && $capturedItems && count($capturedItems) > 0;
    }
}
