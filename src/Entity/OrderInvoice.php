<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Payever\Bundle\PaymentBundle\Entity\Repository\OrderInvoiceRepository;

#[ORM\Entity(repositoryClass: OrderInvoiceRepository::class)]
#[ORM\Table(name: 'payever_order_invoices')]
class OrderInvoice
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\Column(name: 'order_id', type: Types::INTEGER, nullable: false)]
    private int $orderId;

    #[ORM\Column(name: 'attachment_id', type: Types::INTEGER, nullable: false)]
    private int $attachmentId;

    #[ORM\Column(name: 'payment_id', type: Types::STRING, nullable: false)]
    private string $paymentId;

    #[ORM\Column(name: 'external_id', type: Types::STRING, nullable: true)]
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
     * @param int $id
     * @return $this
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
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
