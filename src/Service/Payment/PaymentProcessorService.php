<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfigInterface;
use Payever\Bundle\PaymentBundle\Service\Helper\PaymentHelper;
use Payever\Bundle\PaymentBundle\Service\Helper\TransactionHelper;
use Payever\Bundle\PaymentBundle\Service\Helper\UrlHelper;
use Payever\Bundle\PaymentBundle\Service\Management\InvoiceManager;
use Payever\Bundle\PaymentBundle\Service\Payment\Request\PopulatePaymentRequestV3;
use Payever\Sdk\Payments\Enum\Status;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class PaymentProcessorService
{
    private ConfigManager $configManager;

    private UrlHelper $urlHelper;

    private PaymentHelper $paymentHelper;

    private TransactionHelper $transactionHelper;

    private InvoiceManager $invoiceManager;

    private TransactionStatusService $transactionStatusService;

    private PopulatePaymentRequestV3 $populatePaymentRequestV3;

    public function __construct(
        ConfigManager $configManager,
        UrlHelper $urlHelper,
        PaymentHelper $paymentHelper,
        TransactionHelper $transactionHelper,
        InvoiceManager $invoiceManager,
        TransactionStatusService $transactionStatusService,
        PopulatePaymentRequestV3 $populatePaymentRequestV3,
    ) {
        $this->configManager = $configManager;
        $this->urlHelper = $urlHelper;
        $this->paymentHelper = $paymentHelper;
        $this->transactionHelper = $transactionHelper;
        $this->invoiceManager = $invoiceManager;
        $this->transactionStatusService = $transactionStatusService;
        $this->populatePaymentRequestV3 = $populatePaymentRequestV3;
    }

    /**
     * @param Order $order
     * @param Checkout $checkout
     * @param PayeverConfigInterface $config
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function getPaymentUrl(Order $order, Checkout $checkout, PayeverConfigInterface $config): string
    {
        $this->populatePaymentRequestV3
            ->setConfig($config)
            ->setCheckout($checkout);

        if ($config->getIsSubmitMethod()) {
            return $this->populatePaymentRequestV3->createSubmitUrl($order);
        }

        $redirectUrl = $this->populatePaymentRequestV3->createRedirectUrl($order);

        return ($config->getIsRedirectMethod() || $this->configManager->get('payever_payment.is_redirect'))
            ? $redirectUrl
            : $this->urlHelper->generateIframeUrl($redirectUrl);
    }

    /**
     * Finalize Payment.
     *
     * @param PaymentTransaction $paymentTransaction
     * @param string $paymentId
     *
     * @return void
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \Throwable
     */
    public function finalizePayment(PaymentTransaction $paymentTransaction, string $paymentId): void
    {
        $order = $this->transactionHelper->getOrder($paymentTransaction);
        if (!$order) {
            throw new \Exception('Order is not found.');
        }

        $payeverPayment = $this->paymentHelper->retrievePayment($paymentId);
        $this->transactionStatusService->persistTransactionStatus($payeverPayment, $order);

        if ($payeverPayment->getStatus() === Status::STATUS_PAID) {
            $this->invoiceManager->addInvoiceIfApplicable($order, $paymentId);
        }
    }
}
