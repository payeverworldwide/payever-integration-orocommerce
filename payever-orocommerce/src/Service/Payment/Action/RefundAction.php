<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Action;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Payever\Bundle\PaymentBundle\Service\Api\ServiceProvider;
use Payever\Bundle\PaymentBundle\Service\Management\OrderManager;
use Payever\Bundle\PaymentBundle\Service\Helper\CompareHelper;
use Payever\Sdk\Payments\PaymentsApiClient;
use Payever\Sdk\Core\Base\ResponseInterface;
use Payever\Sdk\Core\Http\Response;
use Payever\Sdk\Payments\Http\ResponseEntity\RefundPaymentResponse;
use Psr\Log\LoggerInterface;

class RefundAction implements ActionInterface
{
    private ServiceProvider $serviceProvider;

    private OrderManager $orderManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct(
        ServiceProvider $serviceProvider,
        OrderManager $orderManager,
        LoggerInterface $logger
    ) {
        $this->serviceProvider = $serviceProvider;
        $this->orderManager = $orderManager;
        $this->logger = $logger;
    }

    public function execute(Order $order, string $paymentId, $amount = null): RefundPaymentResponse
    {
        // Amount't can\t be null: Amount should be a positive value
        $amount = $amount ? (float) $amount : $this->orderManager->getAvailableRefundAmount($order);

        try {
            /** @var Response $result */
            $result = $this->getPaymentApiClient()->refundPaymentRequest($paymentId, round($amount, 2));
        } catch (\Exception $exception) {
            $this->logger->critical('Refund action error: ' . $exception->getMessage());

            throw new \LogicException($exception->getMessage());
        }

        $this->logger->info(
            sprintf(
                'Refund action successfully executed for payment %s. Amount: %s',
                $paymentId,
                $amount
            )
        );

        /** @var RefundPaymentResponse $response */
        $response = $result->getResponseEntity();
        if (CompareHelper::areSame((float) $order->getTotal(), (float) $amount)) {
            $this->orderManager->refundOrderItems(
                $order,
                $this->orderManager->getOrderQtyAvailableForRefund($order)
            );

            return $response;
        }

        // Save refunded amount
        $this->orderManager->addRefundedAmount($order, $amount, true);

        return $response;
    }

    /**
     * @return PaymentsApiClient
     */
    private function getPaymentApiClient(): PaymentsApiClient
    {
        return $this->serviceProvider->getPaymentsApiClient();
    }
}
