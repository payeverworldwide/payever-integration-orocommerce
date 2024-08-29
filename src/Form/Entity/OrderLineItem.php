<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Form\Entity;

/**
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class OrderLineItem
{
    /**
     * @var int|null
     */
    private ?int $id;

    /**
     * @var int|null
     */
    private ?int $orderId;

    /**
     * @var string|null
     */
    private ?string $itemType;

    /**
     * @var string|null
     */
    private ?string $itemReference;

    /**
     * @var string|null
     */
    private ?string $name;

    /**
     * @var float|null
     */
    private ?float $unitPrice;

    /**
     * @var float|null
     */
    private ?float $totalPrice;

    /**
     * @var float|null
     */
    private ?float $quantity;

    /**
     * @var float|null
     */
    private ?float $qtyCaptured;

    /**
     * @var float|null
     */
    private ?float $qtyCancelled;

    /**
     * @var float|null
     */
    private ?float $qtyRefunded;

    /**
     * @var float|null
     */
    private $quantityToRefund;

    /**
     * @var float|null
     */
    private $quantityToCapture;

    /**
     * @var float|null
     */
    private $quantityToCancel;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
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
     *
     * @return $this
     */
    public function setOrderId(int $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getItemType(): ?string
    {
        return $this->itemType;
    }

    /**
     * @param string $itemType
     *
     * @return $this
     */
    public function setItemType(string $itemType): self
    {
        $this->itemType = $itemType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getItemReference(): ?string
    {
        return $this->itemReference;
    }

    /**
     * @param string $itemReference
     *
     * @return $this
     */
    public function setItemReference(string $itemReference): self
    {
        $this->itemReference = $itemReference;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getUnitPrice(): ?float
    {
        return $this->unitPrice;
    }

    /**
     * @param float $unitPrice
     *
     * @return $this
     */
    public function setUnitPrice(float $unitPrice): self
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    /**
     * @return float
     */
    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }

    /**
     * @param float $totalPrice
     *
     * @return $this
     */
    public function setTotalPrice(float $totalPrice): self
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    /**
     * @param float $quantity
     *
     * @return $this
     */
    public function setQuantity(float $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getQtyCaptured(): ?float
    {
        return $this->qtyCaptured;
    }

    /**
     * @param float $qtyCaptured
     *
     * @return $this
     */
    public function setQtyCaptured(float $qtyCaptured): self
    {
        $this->qtyCaptured = $qtyCaptured;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getQtyCancelled(): ?float
    {
        return $this->qtyCancelled;
    }

    /**
     * @param float $qtyCancelled
     *
     * @return $this
     */
    public function setQtyCancelled(float $qtyCancelled): self
    {
        $this->qtyCancelled = $qtyCancelled;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getQtyRefunded(): ?float
    {
        return $this->qtyRefunded;
    }

    /**
     * @param float $qtyRefunded
     *
     * @return $this
     */
    public function setQtyRefunded(float $qtyRefunded): self
    {
        $this->qtyRefunded = $qtyRefunded;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getQuantityToRefund(): ?float
    {
        return $this->quantityToRefund;
    }

    /**
     * @param $qty
     *
     * @return $this
     */
    public function setQuantityToRefund($qty): self
    {
        $this->quantityToRefund = $qty;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getQuantityToCapture(): ?float
    {
        return $this->quantityToCapture;
    }

    /**
     * @param $qty
     *
     * @return $this
     */
    public function setQuantityToCapture($qty): self
    {
        $this->quantityToCapture = $qty;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getQuantityToCancel(): ?float
    {
        return $this->quantityToCancel;
    }

    /**
     * @param $qty
     *
     * @return $this
     */
    public function setQuantityToCancel($qty): self
    {
        $this->quantityToCancel = $qty;

        return $this;
    }
}
