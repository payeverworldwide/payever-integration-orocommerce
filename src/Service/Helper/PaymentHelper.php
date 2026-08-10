<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Helper;

use Payever\Bundle\PaymentBundle\Service\Api\ServiceProvider;
use Payever\Sdk\Payments\Enum\Status;
use Payever\Sdk\Payments\Http\MessageEntity\RetrievePaymentResultEntity;
use Payever\Sdk\Payments\Http\ResponseEntity\RetrievePaymentResponse;

class PaymentHelper
{
    const array SUCCESS_STATUSES = [
        Status::STATUS_IN_PROCESS,
        Status::STATUS_ACCEPTED,
        Status::STATUS_PAID,
    ];

    /**
     * @var ServiceProvider
     */
    private ServiceProvider $serviceProvider;

    /**
     * @param ServiceProvider $serviceProvider
     */
    public function __construct(ServiceProvider $serviceProvider)
    {
        $this->serviceProvider = $serviceProvider;
    }

    /**
     * @param string $paymentId
     *
     * @return RetrievePaymentResultEntity
     *
     * @throws \Exception
     */
    public function retrievePaymentStatus(string $paymentId): string
    {
        return $this->retrievePayment($paymentId)->getStatus();
    }

    /**
     * @param string $paymentId
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function isPaymentSuccess(string $paymentId): bool
    {
        return in_array($this->retrievePaymentStatus($paymentId), self::SUCCESS_STATUSES);
    }

    /**
     * @param string $paymentId
     *
     * @return RetrievePaymentResultEntity
     *
     * @throws \Exception
     */
    public function retrievePayment(string $paymentId): RetrievePaymentResultEntity
    {
        $response = $this->serviceProvider
            ->getPaymentsApiClient()
            ->retrievePaymentRequest($paymentId);

        /** @var RetrievePaymentResponse $responseEntity */
        $responseEntity = $response->getResponseEntity();

        /** @var RetrievePaymentResultEntity $payeverPayment */
        return $responseEntity->getResult();
    }
}
