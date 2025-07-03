<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Payever\Bundle\PaymentBundle\Entity\Repository\OrderItemsRepository;

#[ORM\Entity(repositoryClass: OrderItemsRepository::class)]
#[ORM\Table(name: 'payever_order_items')]
class OrderItems
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\Column(name: 'order_id', type: Types::INTEGER, nullable: false)]
    private $orderId;

    #[ORM\Column(name: 'item_type', type: Types::STRING, length: 255, nullable: true)]
    private $itemType;

    #[ORM\Column(name: 'item_reference', type: Types::STRING, length: 255, nullable: true)]
    private $itemReference;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255, nullable: true)]
    private $name;

    #[ORM\Column(name: 'unit_price', type: Types::FLOAT, nullable: true)]
    private $unitPrice;

    #[ORM\Column(name: 'total_price', type: Types::FLOAT, nullable: true)]
    private $totalPrice;

    #[ORM\Column(name: 'quantity', type: Types::FLOAT, nullable: true)]
    private $quantity;

    #[ORM\Column(name: 'qty_captured', type: Types::FLOAT, nullable: true)]
    private $qtyCaptured;

    #[ORM\Column(name: 'qty_cancelled', type: Types::FLOAT, nullable: true)]
    private $qtyCancelled;

    #[ORM\Column(name: 'qty_refunded', type: Types::FLOAT, nullable: true)]
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
