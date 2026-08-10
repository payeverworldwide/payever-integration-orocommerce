<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Notification;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Oro\Bundle\OrderBundle\Entity\Order;
use Payever\Bundle\PaymentBundle\Service\Helper\OrderHelper;
use Payever\Bundle\PaymentBundle\Service\Helper\TransactionHelper;
use Payever\Bundle\PaymentBundle\Service\Management\CheckoutManager;
use Payever\Bundle\PaymentBundle\Service\Management\PaymentActionManager;
use Payever\Bundle\PaymentBundle\Service\Payment\Notification\Handler\B2BPaymentHandler;
use Payever\Bundle\PaymentBundle\Service\Payment\Notification\Handler\HandlerNotFoundException;
use Payever\Bundle\PaymentBundle\Service\Payment\Notification\Handler\HandlerRegistry;
use Payever\Bundle\PaymentBundle\Service\Payment\Request\PopulatePaymentRequestV3;
use Payever\Bundle\PaymentBundle\Service\Payment\TransactionStatusService;
use Payever\Sdk\Payments\Enum\Status;
use Payever\Sdk\Payments\Http\RequestEntity\NotificationRequestEntity;
use Payever\Sdk\Payments\Notification\MessageEntity\NotificationActionResultEntity;
use Payever\Sdk\Payments\Notification\MessageEntity\NotificationResultEntity;
use Payever\Sdk\Payments\Notification\NotificationHandlerInterface;
use Payever\Sdk\Payments\Notification\NotificationResult;
use Psr\Log\LoggerInterface;

class NotificationHandler implements NotificationHandlerInterface
{
    private TransactionHelper $transactionHelper;
    private TransactionStatusService $transactionStatusService;
    private PaymentActionManager $paymentActionManager;
    private HandlerRegistry $handlerRegistry;
    private B2BPaymentHandler $b2bPaymentHandler;
    private CheckoutManager $checkoutManager;
    private OrderHelper $orderHelper;
    private LoggerInterface $logger;

    public function __construct(
        TransactionHelper $transactionHelper,
        TransactionStatusService $transactionStatusService,
        PaymentActionManager $paymentActionManager,
        HandlerRegistry $handlerRegistry,
        B2BPaymentHandler $b2bPaymentHandler,
        CheckoutManager $checkoutManager,
        OrderHelper $orderHelper,
        LoggerInterface $logger
    ) {
        $this->transactionHelper = $transactionHelper;
        $this->transactionStatusService = $transactionStatusService;
        $this->paymentActionManager = $paymentActionManager;
        $this->handlerRegistry = $handlerRegistry;
        $this->b2bPaymentHandler = $b2bPaymentHandler;
        $this->checkoutManager = $checkoutManager;
        $this->orderHelper = $orderHelper;
        $this->logger = $logger;
    }

    /**
     * @param NotificationRequestEntity $notification
     * @param NotificationResult $notificationResult
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \Throwable
     */
    public function handleNotification(
        NotificationRequestEntity $notification,
        NotificationResult $notificationResult
    ): void {
        $notificationPaymentEntity = $notification->getPayment();
        if ($notificationPaymentEntity->getStatus() === Status::STATUS_NEW) {
            $notificationResult->addMessage(
                'Notification rejected: Notification processing is skipped; reason: stalled new status'
            );
            return;
        }

        $reference = $notificationPaymentEntity->getReference();
        $order = str_contains($reference, PopulatePaymentRequestV3::CHECKOUT_REFERENCE_PREFIX)
            ? $this->getOrderByCheckout($reference, $notificationPaymentEntity->getId())
            : $this->getOrderByReference($reference);

        if (!$order) {
            $notificationResult->addMessage('Order is not found');

            return;
        }

        $orderReference = $order->getIdentifier();

        $notificationDateTime = $notification->getCreatedAt();
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

        if ($this->shouldBeRejectedIfExpiredStatus($notificationPaymentEntity)) {
            $notificationResult->addMessage(
                'Notification rejected: Notification expire processing is skipped; reason: order already processed'
            );

            return;
        }

        // Update company search id if exists
        if ($this->b2bPaymentHandler->isApplicable($notificationPaymentEntity)) {
            $this->b2bPaymentHandler->execute($notificationPaymentEntity);
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
        $this->transactionStatusService->persistTransactionStatus($notificationPaymentEntity, $order);
        $this->transactionStatusService->updateNotificationTimestamp($orderReference, $notificationTimestamp);

        $notificationResult->addMessage('Payment state was updated');
    }

    /**
     * @param string $checkoutReference
     * @param string $paymentId
     *
     * @return Order|null
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\Exception\NotSupported
     * @throws \Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException
     * @throws \Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException
     * @throws \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @throws \Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException
     */
    private function getOrderByCheckout(string $checkoutReference, string $paymentId): ?Order
    {
        $checkoutId = (int)ltrim($checkoutReference, PopulatePaymentRequestV3::CHECKOUT_REFERENCE_PREFIX);
        $checkout = $this->checkoutManager->getOroCheckout($checkoutId);

        // Complete checkout payment and create an order
        $payeverCheckout = $this->checkoutManager->completeCheckoutPayment($checkout, $paymentId);
        $orderId = $payeverCheckout->getOrderId();

        return $this->orderHelper->getOrderByID($orderId);
    }

    /**
     * @param string $orderReference
     *
     * @return Order|null
     */
    private function getOrderByReference(string $orderReference): ?Order
    {
        return $this->transactionHelper->getOrderByIdentifier($orderReference);
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

    /**
     * Checks if a notification should be rejected.
     *
     * @param NotificationResultEntity $notificationPaymentEntity
     * @return bool
     */
    private function shouldBeRejectedIfExpiredStatus(
        NotificationResultEntity $notificationPaymentEntity
    ): bool {
        return in_array($notificationPaymentEntity->getStatus(), [Status::STATUS_DECLINED, Status::STATUS_FAILED])
            && in_array($notificationPaymentEntity->getSpecificStatus(), ['ORDER_EXPIRED', 'CHECKOUT_EXPIRED'] )
            && $this->transactionHelper->isPaid($notificationPaymentEntity->getReference());
    }
}
