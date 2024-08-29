<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Action;

use Oro\Bundle\OrderBundle\Entity\Order;
use Payever\Sdk\Payments\Http\ResponseEntity\SettlePaymentResponse;

class SettleAction extends ActionAbstract
{
    /**
     * @param Order $order
     *
     * @return SettlePaymentResponse
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function execute(Order $order): SettlePaymentResponse
    {
        $paymentId = $this->orderHelper->getPaymentId($order);
        if (!$paymentId) {
            throw new \LogicException('Payment transaction does not have stored payment id.');
        }

        try {
            /** @var SettlePaymentResponse $result */
            $result = $this->getPaymentApiClient()->settlePaymentRequest($paymentId);
        } catch (\Exception $exception) {
            $this->logger->critical('Settle action error: ' . $exception->getMessage());

            throw new \LogicException($exception->getMessage());
        }

        $this->logger->info(
            sprintf(
                'Settle action successfully executed for payment %s. Amount: %s',
                $paymentId,
                $order->getTotal()
            )
        );

        /** @var SettlePaymentResponse $response */
        $response = $result->getResponseEntity();
        $this->logger->debug('Settle amount action response', $response->toArray());

        // Save settled amount
        $this->orderManager->addSettledAmount($order, (float) $order->getTotal(), true);

        return $response;
    }
}
