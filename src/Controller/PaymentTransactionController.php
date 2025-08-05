<?php
// phpcs:ignoreFile

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Controller;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Payever\Bundle\PaymentBundle\Method\Config\Provider\PayeverConfigProvider;
use Payever\Bundle\PaymentBundle\Method\Provider\PayeverMethodProvider;
use Payever\Bundle\PaymentBundle\Service\Payment\PaymentProcessorService;
use Payever\Bundle\PaymentBundle\Service\Helper\TransactionHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PaymentTransactionController extends AbstractController
{
    /**
     * Used by widget info.
     * @see Resources/views/PaymentTransaction/widget/info.html.twig
     *
     * @Route("/info/{paymentTransactionId}/", name="payever_payment_transaction_info")
     * @Template
     */
    public function infoAction(
        Request $request,
        TransactionHelper $transactionHelper,
        PayeverMethodProvider $payverMethodProvider,
        PayeverConfigProvider $payeverConfigProvider,
        PaymentProcessorService $paymentProcessor
    ) {
        try {
            $id = (int) $request->get('paymentTransactionId');
            if (!$id) {
                throw new \Exception('Payment transaction id not found');
            }

            $paymentTransaction = $transactionHelper->getPaymentTransactionByID($id);
            if (!$paymentTransaction) {
                throw new \Exception('Payment transaction not found');
            }

            $paymentMethod = $payverMethodProvider->getPaymentMethod(
                $paymentTransaction->getPaymentMethod()
            );

            $payment = $paymentProcessor
                ->setConfig($payeverConfigProvider->getPaymentConfig($paymentMethod->getIdentifier()))
                ->retrievePayment($paymentTransaction);

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
        } catch (\Exception $exception) {
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

    public static function getSubscribedServices(): array
    {
        return array_merge(
            [
                TransactionHelper::class,
                PayeverMethodProvider::class,
                PayeverConfigProvider::class,
                PaymentProcessorService::class,
            ],
            parent::getSubscribedServices()
        );
    }
}
