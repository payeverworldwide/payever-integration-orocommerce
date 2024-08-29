<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="payever_order_invoices")
 * @ORM\Entity(repositoryClass="Payever\Bundle\PaymentBundle\Entity\Repository\OrderInvoiceRepository")
 *
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class OrderInvoice
{
    /**
     * Unique identifier field.
     *
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="id", type="integer", nullable=false)
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="order_id", type="integer", nullable=false)
     */
    private int $orderId;

    /**
     * @var int
     *
     * @ORM\Column(name="attachment_id", type="integer", nullable=false)
     */
    private int $attachmentId;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_id", type="string", nullable=false)
     */
    private string $paymentId;

    /**
     * @var string
     *
     * @ORM\Column(name="external_id", type="string", nullable=true)
     */
    private string $externalId;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getOrderId(): int
    {
        return $this->orderId;
    }

    /**
     * @param int $orderId
     * @return $this
     */
    public function setOrderId(int $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * @return int
     */
    public function getAttachmentId(): int
    {
        return $this->attachmentId;
    }

    /**
     * @param int $attachmentId
     * @return $this
     */
    public function setAttachmentId(int $attachmentId): self
    {
        $this->attachmentId = $attachmentId;

        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    /**
     * @param string $paymentId
     * @return $this
     */
    public function setPaymentId(string $paymentId): self
    {
        $this->paymentId = $paymentId;

        return $this;
    }

    /**
     * @return string
     */
    public function getExternalId(): string
    {
        return $this->externalId;
    }

    /**
     * @param string $externalId
     * @return $this
     */
    public function setExternalId(string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }
}
