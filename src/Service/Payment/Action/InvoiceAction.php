<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Action;

use Oro\Bundle\OrderBundle\Entity\Order;
use Payever\Bundle\PaymentBundle\Service\Management\PaymentActionManager;
use Payever\Sdk\Core\Http\Response;
use Payever\Sdk\Payments\Http\ResponseEntity\InvoicePaymentResponse;

class InvoiceAction extends ActionAbstract
{
    /**
     * @param Order $order
     * @param float|null $amount
     *
     * @return InvoicePaymentResponse
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function execute(Order $order, ?float $amount): InvoicePaymentResponse
    {
        $paymentId = $this->orderHelper->getPaymentId($order);
        if (!$paymentId) {
            throw new \LogicException('Payment transaction does not have stored payment id.');
        }

        // Amount't can\t be null: Amount should be a positive value
        if (is_null($amount)) {
            $amount = $this->orderManager->getAvailableRefundAmount($order);
        }

        $paymentAction = $this->paymentActionManager->addAction(
            $order,
            PaymentActionManager::ACTION_INVOICE,
            PaymentActionManager::SOURCE_EXTERNAL,
            (float) $amount
        );

        try {
            /** @var Response $result */
            $result = $this->getPaymentApiClient()->invoicePaymentRequest(
                $paymentId,
                round($amount, 2),
                $paymentAction->getIdentifier()
            );
        } catch (\Exception $exception) {
            $this->logger->critical('Invoice action error: ' . $exception->getMessage());

            throw new \LogicException($exception->getMessage());
        }

        $this->logger->info(
            sprintf(
                'Invoice action successfully executed for payment %s. Amount: %s',
                $paymentId,
                $amount
            )
        );

        /** @var InvoicePaymentResponse $response */
        $response = $result->getResponseEntity();
        $this->logger->debug('Invoice amount action response', $response->toArray());

        // Save refunded amount
        $this->orderManager->addInvoicedAmount($order, $amount, true);

        return $response;
    }
}
