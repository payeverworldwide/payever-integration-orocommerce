<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Notification\Handler;

use Payever\Sdk\Payments\Notification\MessageEntity\NotificationResultEntity;

interface HandlerInterface
{
    /**
     * Execute handler.
     *
     * @param NotificationResultEntity $notificationResultEntity
     * @return array
     */
    public function execute(NotificationResultEntity $notificationResultEntity): array;

    /**
     * Checks if handler is applicable.
     *
     * @param NotificationResultEntity $notificationResultEntity
     * @return bool
     */
    public function isApplicable(NotificationResultEntity $notificationResultEntity): bool;
}
