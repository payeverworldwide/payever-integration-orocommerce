<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
#[ORM\Entity(repositoryClass: \Payever\Bundle\PaymentBundle\Entity\Repository\OrderItemsRepository::class)]
#[ORM\Table(name: 'payever_order_items')]
class OrderItems
{
    /**
     * Unique identifier field.
     *
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    private $id;

    /**
     * @var int
     */
    #[ORM\Column(name: 'order_id', type: 'integer', nullable: false)]
    private $orderId;

    /**
     * @var string
     */
    #[ORM\Column(name: 'item_type', type: 'string', length: 255, nullable: true)]
    private $itemType;

    /**
     * @var string
     */
    #[ORM\Column(name: 'item_reference', type: 'string', length: 255, nullable: true)]
    private $itemReference;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    private $name;

    /**
     * @var float
     */
    #[ORM\Column(name: 'unit_price', type: 'float', nullable: true)]
    private $unitPrice;

    /**
     * @var float
     */
    #[ORM\Column(name: 'total_price', type: 'float', nullable: true)]
    private $totalPrice;

    /**
     * @var float
     */
    #[ORM\Column(name: 'quantity', type: 'float', nullable: true)]
    private $quantity;

    /**
     * @var float
     */
    #[ORM\Column(name: 'qty_captured', type: 'float', nullable: true)]
    private $qtyCaptured;

    /**
     * @var float
     */
    #[ORM\Column(name: 'qty_cancelled', type: 'float', nullable: true)]
    private $qtyCancelled;

    /**
     * @var float
     */
    #[ORM\Column(name: 'qty_refunded', type: 'float', nullable: true)]
    private $qtyRefunded;

    /**
     * @return int
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
     * @return string
     */
    public function getItemType(): string
    {
        return $this->itemType;
    }

    /**
     * @param string $itemType
     * @return $this
     */
    public function setItemType(string $itemType): self
    {
        $this->itemType = $itemType;

        return $this;
    }

    /**
     * @return string
     */
    public function getItemReference(): string
    {
        return $this->itemReference;
    }

    /**
     * @param string $itemReference
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
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return float
     */
    public function getUnitPrice(): float
    {
        return (float) $this->unitPrice;
    }

    /**
     * @param float $price
     * @return $this
     */
    public function setUnitPrice(float $price): self
    {
        $this->unitPrice = $price;

        return $this;
    }

    /**
     * @return float
     */
    public function getTotalPrice(): float
    {
        return (float) $this->totalPrice;
    }

    /**
     * @param float $price
     * @return $this
     */
    public function setTotalPrice(float $price): self
    {
        $this->totalPrice = $price;

        return $this;
    }

    /**
     * @return float
     */
    public function getQuantity(): float
    {
        return (float) $this->quantity;
    }

    /**
     * @param float $qty
     * @return $this
     */
    public function setQuantity(float $qty): self
    {
        $this->quantity = $qty;

        return $this;
    }

    /**
     * @return float
     */
    public function getQtyCaptured(): float
    {
        return (float) $this->qtyCaptured;
    }

    /**
     * @param float $qty
     * @return $this
     */
    public function setQtyCaptured(float $qty): self
    {
        $this->qtyCaptured = $qty;

        return $this;
    }

    /**
     * @return float
     */
    public function getQtyCancelled(): float
    {
        return (float) $this->qtyCancelled;
    }

    /**
     * @param float $qty
     * @return $this
     */
    public function setQtyCancelled(float $qty): self
    {
        $this->qtyCancelled = $qty;

        return $this;
    }

    /**
     * @return float
     */
    public function getQtyRefunded(): float
    {
        return (float) $this->qtyRefunded;
    }

    /**
     * @param float $qty
     * @return $this
     */
    public function setQtyRefunded(float $qty): self
    {
        $this->qtyRefunded = $qty;

        return $this;
    }

    /**
     * @return float
     */
    public function getCanBeCaptured(): float
    {
        return $this->getQuantity() - $this->getQtyCaptured() - $this->getQtyCancelled();
    }

    /**
     * @return float
     */
    public function getCanBeCancelled(): float
    {
        return $this->getCanBeCaptured();
    }

    /**
     * @return float
     */
    public function getCanBeRefunded(): float
    {
        return $this->getQtyCaptured() - $this->getQtyRefunded();
    }
}
