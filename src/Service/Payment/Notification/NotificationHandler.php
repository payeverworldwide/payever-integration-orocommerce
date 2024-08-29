<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Notification;

use Payever\Bundle\PaymentBundle\Service\Management\PaymentActionManager;
use Payever\Bundle\PaymentBundle\Service\Payment\TransactionStatusService;
use Payever\Bundle\PaymentBundle\Service\Payment\Notification\Handler\HandlerRegistry;
use Payever\Bundle\PaymentBundle\Service\Payment\Notification\Handler\HandlerNotFoundException;
use Payever\Sdk\Payments\Http\RequestEntity\NotificationRequestEntity;
use Payever\Sdk\Payments\Notification\MessageEntity\NotificationActionResultEntity;
use Payever\Sdk\Payments\Notification\MessageEntity\NotificationResultEntity;
use Payever\Sdk\Payments\Notification\NotificationHandlerInterface;
use Payever\Sdk\Payments\Notification\NotificationResult;
use Psr\Log\LoggerInterface;

class NotificationHandler implements NotificationHandlerInterface
{
    private TransactionStatusService $transactionStatusService;
    private PaymentActionManager $paymentActionManager;
    private HandlerRegistry $handlerRegistry;
    private LoggerInterface $logger;

    public function __construct(
        TransactionStatusService $transactionStatusService,
        PaymentActionManager $paymentActionManager,
        HandlerRegistry $handlerRegistry,
        LoggerInterface $logger
    ) {
        $this->transactionStatusService = $transactionStatusService;
        $this->paymentActionManager = $paymentActionManager;
        $this->handlerRegistry = $handlerRegistry;
        $this->logger = $logger;
    }

    /**
     * @param NotificationRequestEntity $notification
     * @param NotificationResult $notificationResult
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

        /** @var NotificationActionResultEntity $action */
        $notificationAction = $notification->getAction();
        if ($notificationAction && $this->shouldBeRejectedAction($notificationAction)) {
            $notificationResult
                ->addMessage(
                    sprintf(
                        'Rejecting notification: This action was handled before. Order %s, Payment %s. ID: %s',
                        $orderReference,
                        $notificationPaymentEntity->getId(),
                        $notificationAction->getUniqueIdentifier()
                    )
                );

            return;
        }

        // Handle capture/refund/cancel notification
        if ($this->isApplicablePartialAction($notificationPaymentEntity)) {
            $this->logger->info(sprintf(
                '[Notification] Handle payment action. Order ID: %s. Payment ID: %s',
                $orderReference,
                $notificationPaymentEntity->getId()
            ));

            try {
                $this->handlerRegistry->getHandler($notificationPaymentEntity)->execute($notificationPaymentEntity);
                $this->transactionStatusService->updateNotificationTimestamp($orderReference, $notificationTimestamp);
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

        // Applicable for full transactions
        $this->transactionStatusService->persistTransactionStatus($notificationPaymentEntity);
        $this->transactionStatusService->updateNotificationTimestamp($orderReference, $notificationTimestamp);

        $notificationResult->addMessage('Payment state was updated');
    }

    /**
     * Checks if the notification is applicable for order items / amount handling.
     *
     * @param NotificationResultEntity $notificationPaymentEntity
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function isApplicablePartialAction(NotificationResultEntity $notificationPaymentEntity): bool
    {
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
            return true;
        }

        return false;
    }

    /**
     * Determines whether the given notification action should be rejected.
     *
     * @param NotificationActionResultEntity $notificationAction The notification action to evaluate.
     * @return bool Returns true if the notification action should be rejected, false otherwise.
     */
    private function shouldBeRejectedAction(NotificationActionResultEntity $notificationAction): bool
    {
        $action = $this->paymentActionManager->loadByIdentifier($notificationAction->getUniqueIdentifier());
        return !is_null($action);
    }
}
