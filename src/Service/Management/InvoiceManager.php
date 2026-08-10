<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Management;

use Oro\Bundle\OrderBundle\Entity\Order;
use Payever\Bundle\PaymentBundle\Entity\OrderInvoice;
use Payever\Bundle\PaymentBundle\Entity\Repository\OrderInvoiceRepository;
use Payever\Bundle\PaymentBundle\Service\Helper\PaymentMethodHelper;
use Payever\Bundle\PaymentBundle\Service\Payment\InvoiceService;
use Psr\Log\LoggerInterface;

class InvoiceManager
{
    private InvoiceService $invoiceService;
    private OrderInvoiceRepository $orderInvoiceRepository;
    private PaymentMethodHelper $paymentMethodHelper;
    private LoggerInterface $logger;

    public function __construct(
        InvoiceService $invoiceService,
        OrderInvoiceRepository $orderInvoiceRepository,
        PaymentMethodHelper $paymentMethodHelper,
        LoggerInterface $logger
    ) {
        $this->invoiceService = $invoiceService;
        $this->orderInvoiceRepository = $orderInvoiceRepository;
        $this->paymentMethodHelper = $paymentMethodHelper;
        $this->logger = $logger;
    }

    public function addInvoiceIfApplicable(Order $order, string $paymentId): ?OrderInvoice
    {
        if (!$this->paymentMethodHelper->isB2BMethod($order)) {
            return null;
        }

        $invoices = $this->orderInvoiceRepository->findByOrder($order);
        if (count($invoices) > 0) {
            return $invoices[0];
        }

        $params = [
            InvoiceService::INVOICE_NUMBER => $order->getIdentifier(),
            InvoiceService::INVOICE_DATE => $this->invoiceService->getOrderInvoiceDate(),
            InvoiceService::INVOICE_COMMENT => '',
            InvoiceService::INVOICE_SEND => false,
            InvoiceService::INVOICE_PAYMENT_ID => $paymentId,
            InvoiceService::INVOICE_EXTERNAL_ID => $order->getBillingAddress()->getPayeverExternalId()
        ];

        $invoice = null;
        try {
            $invoice = $this->invoiceService->createInvoice($order, $params);
            $this->logger->info('Invoice document has been created', [$order->getId()]);
        } catch (\Exception $exception) {
            $this->logger->critical('Invoice create Exception: ' . $exception->getMessage());
        }

        return $invoice;
    }
}
