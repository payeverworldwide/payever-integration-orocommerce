<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Notification\Handler;

use Payever\Sdk\Payments\Notification\MessageEntity\NotificationResultEntity;

/**
 * Register all possible notification handlers used in the application
 */
class HandlerRegistry
{
    private iterable $handlers;

    /**
     * @param iterable|HandlerInterface[] $handlers
     */
    public function __construct(iterable $handlers)
    {
        $this->handlers = $handlers;
    }

    public function getHandler(
        NotificationResultEntity $notificationResultEntity
    ): HandlerInterface {
        foreach ($this->handlers as $handler) {
            if ($handler->isApplicable($notificationResultEntity)) {
                return $handler;
            }
        }

        throw new HandlerNotFoundException('No possible notification handler.');
    }
}
