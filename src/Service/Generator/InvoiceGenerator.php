<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Generator;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Payever\Bundle\PaymentBundle\Method\Config\Provider\PayeverConfigProvider;
use Payever\Bundle\PaymentBundle\Service\Helper\OrderItemHelper;

class InvoiceGenerator
{
    /**
     * @var OrderItemHelper
     */
    private OrderItemHelper $orderItemHelper;

    /**
     * @var PaymentTransactionProvider
     */
    private PaymentTransactionProvider $paymentTransactionProvider;

    private PayeverConfigProvider $payeverConfigProvider;

    /**
     * @param OrderItemHelper $orderItemHelper
     * @param PaymentTransactionProvider $paymentTransactionProvider
     * @param PayeverConfigProvider $payeverConfigProvider
     */
    public function __construct(
        OrderItemHelper $orderItemHelper,
        PaymentTransactionProvider $paymentTransactionProvider,
        PayeverConfigProvider $payeverConfigProvider,
    ) {
        $this->orderItemHelper = $orderItemHelper;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
        $this->payeverConfigProvider = $payeverConfigProvider;
    }

    /**
     * @param Order $order
     * @param string $number
     * @param \DateTime $date
     * @param string $comment
     *
     * @return string
     */
    public function generate(Order $order, string $number, \DateTime $date, string $comment)
    {
        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetDrawColor(190, 190, 190);
        $pdf->SetTextColor(20, 20, 20);
        $pdf->Ln(15);

        // Invoice details
        $pdf->SetFont('helvetica', '', 20);
        $pdf->Cell(0, 10, 'Invoice ' . $number, 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Ln(20);

        $this->renderOrderInfo($pdf, $order, $date);
        $this->renderTableHeader($pdf);
        $this->renderTableContent($pdf, $order);
        $this->renderTotals($pdf, $order);
        $this->renderComment($pdf, $comment);

        return $pdf->Output('S');
    }

    /**
     * @param \FPDF $pdf
     * @param Order $order
     * @return void
     */
    private function renderOrderInfo(\FPDF $pdf, Order $order, \DateTime $date)
    {
        $address = $order->getBillingAddress();

        // Billing Information
        $billingY = $pdf->GetY();
        $pdf->Cell(100, 5, $address->getFirstName() . ' ' . $address->getLastName(), 0, 1);
        $pdf->Cell(100, 5, $address->getStreet() . ' ' . $address->getStreet2(), 0, 1);
        $pdf->Cell(100, 5, $address->getPostalCode() . ' ' . $address->getCity(), 0, 1);
        $pdf->Cell(100, 5, $address->getCountry(), 0, 1);

        // Order Information
        $pdf->SetY($billingY);
        $pdf->setX(100);
        $pdf->Cell(100, 5, sprintf('Order no.: %s', $order->getIdentifier()), 0, 1, 'R');
        $pdf->setX(100);
        $pdf->Cell(100, 5, sprintf('Order date: %s', $order->getCreatedAt()->format('d M Y')), 0, 1, 'R');
        $pdf->setX(100);
        $pdf->Cell(100, 5, sprintf('Date: %s', $date->format('d M Y')), 0, 1, 'R');
        $pdf->Ln(12);
    }

    /**
     * @param \FPDF $pdf
     * @return void
     */
    private function renderTableHeader(\FPDF $pdf): void
    {
        // Table header
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 10, 'Prod. sku');
        $pdf->Cell(60, 10, 'Prod. name');
        $pdf->Cell(30, 10, 'Quantity', 0, 0, 'R');
        $pdf->Cell(30, 10, 'Unit price', 0, 0, 'R');
        $pdf->Cell(30, 10, 'Total', 0, 0, 'R');
        $pdf->Ln(15);

        $pdf->Line($pdf->GetX(), $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(5);
    }

    /**
     * @param \FPDF $pdf
     * @param Order $order
     * @return void
     */
    private function renderTableContent(\FPDF $pdf, Order $order): void
    {
        // Table content
        $pdf->SetFont('helvetica', '', 10);

        $orderItems = $this->orderItemHelper->getOrderItems($order);
        foreach ($orderItems as $item) {
            if (OrderItemHelper::TYPE_SHIPPING === $item['type']) {
                continue;
            }

            $pdf->Cell(40, 10, $item['sku']);
            $pdf->Cell(60, 10, $item['name']);
            $pdf->Cell(30, 10, $item['quantity'], 0, 0, 'R');
            $pdf->Cell(30, 10, $item['unit_price_incl_tax'], 0, 0, 'R');
            $pdf->Cell(30, 10, $item['total_price_incl_tax'], 0, 0, 'R');
            $pdf->Ln(8);
        }

        $pdf->Ln(7);

        $pdf->Line($pdf->GetX(), $pdf->GetY(), 200, $pdf->GetY());
    }

    /**
     * @param \FPDF $pdf
     * @param Order $order
     * @return void
     */
    private function renderTotals(\FPDF $pdf, Order $order): void
    {
        $shippingName = $this->orderItemHelper->getShippingLabel($order->getShippingMethod());

        $transaction = $this->paymentTransactionProvider->getPaymentTransaction($order, [], ['id' => Criteria::ASC]);
        $paymentMethod = $this->payeverConfigProvider->getPaymentConfig($transaction->getPaymentMethod());

        // Totals
        $pdf->Ln(5);
        $pdf->Cell(130);
        $pdf->Cell(30, 10, 'Subtotal:', 0, 0, 'R');
        $pdf->Cell(30, 10, (float)$order->getSubtotal() . ' ' . $order->getCurrency(), 0, 0, 'R');
        $pdf->Ln(8);

        $pdf->Cell(130);
        $pdf->Cell(30, 10, 'Shipping:', 0, 0, 'R');
        $pdf->Cell(30, 10, (float)$order->getShippingCost()->getValue() . ' ' . $order->getCurrency(), 0, 0, 'R');
        $pdf->Ln(8);

        $pdf->Cell(130);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(30, 10, 'Total:', 0, 0, 'R');
        $pdf->Cell(30, 10, (float)$order->getTotal() . ' ' . $order->getCurrency(), 0, 0, 'R');
        $pdf->Ln(12);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 10, 'Payment Method:');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(30, 10, $paymentMethod->get('label'));
        $pdf->Ln(8);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 10, 'Shipping Method:');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(30, 10, $shippingName);
    }

    /**
     * @param \FPDF $pdf
     * @param string $comment
     * @return void
     */
    private function renderComment(\FPDF $pdf, string $comment): void
    {
        if (!$comment) {
            return;
        }

        $pdf->Ln(8);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 10, 'Comment:');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(30, 10, $comment);
    }
}
