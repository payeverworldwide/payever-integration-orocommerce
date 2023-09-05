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
use Payever\Sdk\Payments\Http\ResponseEntity\CancelPaymentResponse;
use Psr\Log\LoggerInterface;

class CancelAction implements ActionInterface
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

    public function execute(Order $order, string $paymentId, $amount = null): CancelPaymentResponse
    {
        if ($amount) {
            $amount = floatval($amount);
            $amount = round($amount, 2);
        }

        try {
            /** @var CancelPaymentResponse $result */
            $result = $this->getPaymentApiClient()->cancelPaymentRequest($paymentId, $amount);
        } catch (\Exception $exception) {
            $this->logger->critical('Cancel action error: ' . $exception->getMessage());

            throw new \LogicException($exception->getMessage());
        }

        $this->logger->info(
            sprintf(
                'Cancel action successfully executed for payment %s. Amount: %s',
                $paymentId,
                $amount
            )
        );

        /** @var CancelPaymentResponse $response */
        $response = $result->getResponseEntity();
        if (!$amount || CompareHelper::areSame((float) $order->getTotal(), (float) $amount)) {
            $this->orderManager->cancelOrderItems(
                $order,
                $this->orderManager->getOrderQtyAvailableForCancel($order)
            );

            return $response;
        }

        // Save cancelled amount
        $this->orderManager->addCancelledAmount($order, $amount, true);

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
