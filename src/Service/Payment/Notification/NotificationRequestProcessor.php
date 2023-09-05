<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Notification;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Payever\Sdk\Core\Lock\LockInterface;
use Payever\Sdk\Payments\Http\MessageEntity\RetrievePaymentResultEntity;
use Payever\Sdk\Payments\Notification\NotificationHandlerInterface;
use Payever\Sdk\Payments\Notification\NotificationRequestProcessor as BaseProcessor;
use Payever\Bundle\PaymentBundle\Constant\QueryConstant;
use Payever\Bundle\PaymentBundle\Service\Api\ServiceProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class NotificationRequestProcessor extends BaseProcessor
{
    public const NOTIFICATION_TYPE = 'raw_request';

    /**
     * @var RequestStack
     */
    private RequestStack $requestStack;

    /**
     * @var ServiceProvider
     */
    private ServiceProvider $serviceProvider;

    /**
     * @var ConfigManager
     */
    private ConfigManager $configManager;

    public function __construct(
        NotificationHandlerInterface $handler,
        LockInterface $lock,
        LoggerInterface $logger,
        RequestStack $request,
        ServiceProvider $serviceProvider,
        ConfigManager $configManager
    ) {
        parent::__construct($handler, $lock, $logger);

        $this->requestStack = $request;
        $this->serviceProvider = $serviceProvider;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    protected function unserializePayload($payload)
    {
        $notificationRequestEntity = parent::unserializePayload($payload);
        $notificationRequestEntity->setNotificationType(self::NOTIFICATION_TYPE);
        $notificationRequestEntity->setNotificationTypesAvailable([self::NOTIFICATION_TYPE]);

        return $notificationRequestEntity;
    }

    /**
     * {@inheritDoc}
     */
    public function getRequestPayload(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        $paymentId = $request->query->get(QueryConstant::PARAMETER_PAYMENT_ID, '');
        $signature = $request->headers->get(QueryConstant::HEADER_SIGNATURE);
        $payload = $request->getContent();
        if ($signature) {
            $this->assertSignatureValid($paymentId, $signature);
            return $payload;
        }

        $rawData = !empty($payload) ? json_decode($payload, true) : [];

        /** @var RetrievePaymentResultEntity $payeverPayment */
        $payeverPayment = $this->serviceProvider
            ->getPaymentsApiClient()
            ->retrievePaymentRequest($paymentId)
            ->getResponseEntity()
            ->getResult();

        $notificationDateTime = is_array($rawData) && array_key_exists('created_at', $rawData)
            ? $rawData['created_at']
            : null;

        return json_encode([
            'created_at' => $notificationDateTime,
            'data' => [
                'payment' => $payeverPayment->toArray(),
            ],
        ]);
    }

    private function assertSignatureValid(string $paymentId, string $signature): void
    {
        $expectedSignature = hash_hmac(
            'sha256',
            $this->configManager->get('payever_payment.client_id') . $paymentId,
            $this->configManager->get('payever_payment.client_secret')
        );

        if ($signature !== $expectedSignature) {
            throw new \BadMethodCallException('Notification rejected: invalid signature');
        }
    }
}
