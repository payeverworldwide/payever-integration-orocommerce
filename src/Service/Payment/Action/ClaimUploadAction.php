<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Action;

use Oro\Bundle\OrderBundle\Entity\Order;
use Payever\Bundle\PaymentBundle\Form\Entity\OrderClaimUpload;
use Payever\Sdk\Payments\Http\RequestEntity\ClaimUploadPaymentRequest;
use Payever\Sdk\Payments\Http\ResponseEntity\ClaimUploadPaymentResponse;

class ClaimUploadAction extends ActionAbstract
{
    public function execute(Order $order, OrderClaimUpload $orderClaimUpload): ClaimUploadPaymentResponse
    {
        $paymentId = $this->orderHelper->getPaymentId($order);
        if (!$paymentId) {
            throw new \LogicException('Payment transaction does not have stored payment id.');
        }

        try {
            foreach ($orderClaimUpload->getInvoices() as $invoice) {
                $claimUploadPaymentRequest = new ClaimUploadPaymentRequest();
                $claimUploadPaymentRequest->setFileName($invoice->getFilename());
                $claimUploadPaymentRequest->setMimeType($invoice->getMimeType());
                $claimUploadPaymentRequest->setBase64Content(base64_encode($invoice->getContent()));
                $claimUploadPaymentRequest->setDocumentType(ClaimUploadPaymentRequest::DOCUMENT_TYPE_INVOICE);

                /** @var ClaimUploadPaymentResponse $result */
                $result = $this->getPaymentApiClient()->claimUploadPaymentRequest($paymentId, $claimUploadPaymentRequest);
            }
        } catch (\Exception $exception) {
            $this->logger->critical('Clam upload action error: ' . $exception->getMessage());

            throw new \LogicException($exception->getMessage());
        }

        $this->logger->info(
            sprintf('Claim upload action successfully executed for payment %s.', $paymentId)
        );

        /** @var ClaimUploadPaymentResponse $response */
        $response = $result->getResponseEntity();
        $this->logger->debug('Claim upload action response', $response->toArray());

        return $response;
    }
}
