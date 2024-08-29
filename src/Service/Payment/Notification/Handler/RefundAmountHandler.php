<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Notification\Handler;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Payever\Sdk\Payments\Enum\Status;
use Payever\Sdk\Payments\Notification\MessageEntity\NotificationResultEntity;
use Payever\Sdk\Payments\Notification\NotificationRequestProcessor;

class RefundAmountHandler extends NotificationHandlerAbstract implements HandlerInterface
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
            '%s Handle refund amount action. Order ID: %s. Payment ID: %s',
            NotificationRequestProcessor::LOG_PREFIX,
            $order->getId(),
            $paymentId
        ));

        $refundAmount = $notificationResultEntity->getRefundAmount();
        $totalRefundedAmount = (float) $notificationResultEntity->getTotalRefundedAmount();

        // Check if transaction has handled before
        if ($this->isPaymentTransactionExists($order, PaymentMethodInterface::REFUND, $totalRefundedAmount)) {
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

        // Update total refunded
        $total = $this->orderManager->getOrderTotal($order);
        if ($total) {
            $total->setRefundedTotal($totalRefundedAmount)
                  ->setManual(true);

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

        $this->logger->info(NotificationRequestProcessor::LOG_PREFIX . ' Refund amount. Amount: ' . $refundAmount);

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
        $refundAmount = $notificationResultEntity->getRefundAmount();
        $cancelAmount = $notificationResultEntity->getCancelAmount();

        return in_array($status, [Status::STATUS_REFUNDED, Status::STATUS_CANCELLED]) &&
            (!$captureAmount && !$cancelAmount) &&
            (!$refundedItems || count($refundedItems) === 0) && ($refundAmount && $refundAmount > 0);
    }
}
