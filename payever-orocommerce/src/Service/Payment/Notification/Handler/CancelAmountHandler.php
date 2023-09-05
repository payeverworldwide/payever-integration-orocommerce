<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Notification\Handler;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Payever\Sdk\Payments\Notification\MessageEntity\NotificationResultEntity;
use Payever\Sdk\Payments\Notification\NotificationRequestProcessor;

class CancelAmountHandler extends NotificationHandlerAbstract implements HandlerInterface
{
    /**
     * @iheritdoc
     */
    public function execute(NotificationResultEntity $notificationResultEntity): array
    {
        $orderReference = $notificationResultEntity->getReference();
        $order = $this->transactionHelper->getOrderByID($orderReference);

        $paymentId = $notificationResultEntity->getId();
        $this->logger->info(
            sprintf(
                '%s Handle cancel amount action. Order ID: %s. Payment ID: %s',
                NotificationRequestProcessor::LOG_PREFIX,
                $order->getId(),
                $paymentId
            )
        );

        $cancelAmount = $notificationResultEntity->getCancelAmount();
        $totalCanceledAmount = (float) $notificationResultEntity->getTotalCanceledAmount();

        // Check if transaction has handled before
        if ($this->isPaymentTransactionExists($order, PaymentMethodInterface::CANCEL, $totalCanceledAmount)) {
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

        // Update total cancelled
        $total = $this->orderManager->getOrderTotal($order);
        if ($total) {
            $total->setCancelledTotal($totalCanceledAmount)
                ->setManual(true);

            $this->entityManager->persist($total);
            $this->entityManager->flush($total);
        }

        $this->registerCancelTransaction(
            $order,
            $paymentId,
            $cancelAmount,
            $notificationResultEntity->toArray()
        );

        $this->logger->info(NotificationRequestProcessor::LOG_PREFIX . ' Cancel amount. Amount: ' . $cancelAmount);

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
        $refundedItems = $notificationResultEntity->getRefundedItems();
        $captureAmount = $notificationResultEntity->getCaptureAmount();
        $refundAmount = $notificationResultEntity->getRefundAmount();
        $cancelAmount = $notificationResultEntity->getCancelAmount();

        return (!$capturedItems || count($capturedItems) === 0) &&
            (!$refundedItems || count($refundedItems) === 0) &&
            !$captureAmount && !$refundAmount &&
            ($cancelAmount && $cancelAmount > 0);
    }
}
