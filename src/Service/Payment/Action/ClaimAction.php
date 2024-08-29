<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Action;

use Oro\Bundle\OrderBundle\Entity\Order;
use Payever\Bundle\PaymentBundle\Form\Entity\OrderClaim;
use Payever\Sdk\Payments\Http\RequestEntity\ClaimPaymentRequest;
use Payever\Sdk\Payments\Http\ResponseEntity\ClaimPaymentResponse;
use Symfony\Component\Form\Form;

class ClaimAction extends ActionAbstract
{
    public function execute(Order $order, Form $form): ClaimPaymentResponse
    {
        $paymentId = $this->orderHelper->getPaymentId($order);
        if (!$paymentId) {
            throw new \LogicException('Payment transaction does not have stored payment id.');
        }

        try {
            $claimPaymentRequest = new ClaimPaymentRequest();
            $claimPaymentRequest->setIsDisputed($form->getData()->isDisputed);

            /** @var ClaimPaymentResponse $result */
            $result = $this->getPaymentApiClient()->claimPaymentRequest($paymentId, $claimPaymentRequest);
        } catch (\Exception $exception) {
            $this->logger->critical('Clam action error: ' . $exception->getMessage());

            throw new \LogicException($exception->getMessage());
        }

        $this->logger->info(
            sprintf('Claim action successfully executed for payment %s.', $paymentId)
        );

        /** @var ClaimPaymentResponse $response */
        $response = $result->getResponseEntity();
        $this->logger->debug('Claim action response', $response->toArray());

        return $response;
    }
}
