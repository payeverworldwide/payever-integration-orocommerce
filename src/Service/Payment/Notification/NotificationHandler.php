<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Notification;

use Payever\Bundle\PaymentBundle\Service\Payment\TransactionStatusService;
use Payever\Bundle\PaymentBundle\Service\Payment\Notification\Handler\HandlerRegistry;
use Payever\Bundle\PaymentBundle\Service\Payment\Notification\Handler\HandlerNotFoundException;
use Payever\Sdk\Payments\Http\RequestEntity\NotificationRequestEntity;
use Payever\Sdk\Payments\Notification\NotificationHandlerInterface;
use Payever\Sdk\Payments\Notification\NotificationResult;
use Psr\Log\LoggerInterface;

class NotificationHandler implements NotificationHandlerInterface
{
    private TransactionStatusService $transactionStatusService;
    private HandlerRegistry $handlerRegistry;
    private LoggerInterface $logger;

    public function __construct(
        TransactionStatusService $transactionStatusService,
        HandlerRegistry $handlerRegistry,
        LoggerInterface $logger
    ) {
        $this->transactionStatusService = $transactionStatusService;
        $this->handlerRegistry = $handlerRegistry;
        $this->logger = $logger;
    }

    /**
     * @param NotificationRequestEntity $notification
     * @param NotificationResult $notificationResult
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function handleNotification(
        NotificationRequestEntity $notification,
        NotificationResult $notificationResult
    ): void {
        $notificationPaymentEntity = $notification->getPayment();
        $notificationDateTime = $notification->getCreatedAt();
        $orderReference = $notificationPaymentEntity->getReference();

        $notificationTimestamp = $notificationDateTime instanceof \DateTime
            ? $notificationDateTime->getTimestamp()
            : 0;
        $shouldRejectNotification = $this->transactionStatusService->shouldRejectNotification(
            $orderReference,
            $notificationTimestamp
        );
        if ($shouldRejectNotification) {
            $notificationResult->addMessage('Notification rejected: newer notification already processed');
            return;
        }

        // Handle capture/refund/cancel notification
        $capturedItems = $notificationPaymentEntity->getCapturedItems();
        $refundedItems = $notificationPaymentEntity->getRefundedItems();
        $captureAmount = $notificationPaymentEntity->getCaptureAmount();
        $refundAmount = $notificationPaymentEntity->getRefundAmount();
        $cancelAmount = $notificationPaymentEntity->getCancelAmount();
        if (
            $capturedItems && count($capturedItems) > 0 ||
            $refundedItems && count($refundedItems) > 0 ||
            $captureAmount && $captureAmount > 0  ||
            $refundAmount && $refundAmount > 0 ||
            $cancelAmount && $cancelAmount > 0
        ) {
            $this->logger->info(sprintf(
                '[Notification] Handle payment action. Order ID: %s. Payment ID: %s',
                $orderReference,
                $notificationPaymentEntity->getId()
            ));

            try {
                $this->handlerRegistry->getHandler($notificationPaymentEntity)->execute($notificationPaymentEntity);
                $notificationResult->addMessage('Notification handler is finished');

                return;
            } catch (HandlerNotFoundException $exception) {
                // Use standard way of notification handling
                $notificationResult->addMessage($exception->getMessage());

                return;
            } catch (\Exception $exception) {
                $this->logger->critical($exception->getMessage());
                $notificationResult->addMessage($exception->getMessage());

                return;
            }
        }

        $this->transactionStatusService->persistTransactionStatus($notificationPaymentEntity);
        $this->transactionStatusService->updateNotificationTimestamp($orderReference, $notificationTimestamp);

        $notificationResult->addMessage('Payment state was updated');
    }
}
