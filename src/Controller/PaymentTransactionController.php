<?php
// phpcs:ignoreFile

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Controller;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Payever\Bundle\PaymentBundle\Service\Helper\PaymentHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class PaymentTransactionController extends AbstractController
{
    /**
     * Used by widget info.
     * @see Resources/views/PaymentTransaction/widget/info.html.twig
     *
     * @Route("/info/{paymentTransactionId}", name="payever_payment_transaction_info", requirements={"paymentTransactionId"="\d+"})
     * @ParamConverter("paymentTransaction", options={"mapping": {"paymentTransactionId": "id"}})
     *
     * @Template
     */
    public function infoAction(PaymentTransaction $paymentTransaction, PaymentHelper $paymentHelper): array
    {
        try {
            $payment = $paymentHelper->retrievePayment($paymentTransaction->getReference());
            $details = $payment->getPaymentDetails();

            return [
                'payeverResponse' => [
                    'id' => $payment->getId(),
                    'total' => $payment->getTotal(),
                    'currency' => $payment->getCurrency(),
                    'status' => $payment->getStatus(),
                    'specific_status' => $details->getSpecificStatus(),
                    'customer_name' => $payment->getCustomerName(),
                    'customer_email' => $payment->getCustomerEmail(),
                    'application_number' => $details->getApplicationNumber(),
                    'application_status' => $details->getApplicationStatus(),
                    'usage_text' => $details->getUsageText(),
                ],
            ];
        } catch (\Exception) {
            return [
                'payeverResponse' => [
                    'id' => '',
                    'total' => '',
                    'currency' => '',
                    'status' => '',
                    'specific_status' => '',
                    'customer_name' => '',
                    'customer_email' => '',
                    'application_number' => '',
                    'application_status' => '',
                    'usage_text' => '',
                ],
            ];
        }
    }
}
