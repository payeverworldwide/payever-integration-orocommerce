<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Notification\Handler;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Payever\Sdk\Payments\Notification\MessageEntity\NotificationResultEntity;
use Payever\Sdk\Payments\Notification\NotificationRequestProcessor;

class ShipAmountHandler extends NotificationHandlerAbstract implements HandlerInterface
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
            '%s Handle shipping amount action. Order ID: %s. Payment ID: %s',
            NotificationRequestProcessor::LOG_PREFIX,
            $order->getId(),
            $paymentId
        ));

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

        // Update total captured
        $total = $this->orderManager->getOrderTotal($order);
        if ($total) {
            $total->setCapturedTotal($totalCapturedAmount)
                  ->setManual(true);

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

        $this->logger->info(NotificationRequestProcessor::LOG_PREFIX . ' Captured amount. Amount: ' . $captureAmount);

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
        $captureAmount = $notificationResultEntity->getCaptureAmount();
        $refundAmount = $notificationResultEntity->getRefundAmount();
        $cancelAmount = $notificationResultEntity->getCancelAmount();

        return (!$refundAmount && !$cancelAmount) &&
            (!$capturedItems || count($capturedItems) === 0) &&
            ($captureAmount && $captureAmount > 0);
    }
}
