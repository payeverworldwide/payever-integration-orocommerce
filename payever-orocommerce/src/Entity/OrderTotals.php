<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="payever_order_totals")
 * @ORM\Entity(repositoryClass="Payever\Bundle\PaymentBundle\Entity\Repository\OrderTotalsRepository")
 *
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class OrderTotals
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
    private int $id;

    /**
     * @var int
     *
     * @ORM\Column(name="order_id", type="integer", nullable=false)
     */
    private int $orderId;

    /**
     * @var float
     *
     * @ORM\Column(name="captured_total", type="float", nullable=false)
     */
    private float $capturedTotal;

    /**
     * @var float
     *
     * @ORM\Column(name="cancelled_total", type="float", nullable=false)
     */
    private float $cancelledTotal;

    /**
     * @var float
     *
     * @ORM\Column(name="refunded_total", type="float", nullable=false)
     */
    private float $refundedTotal;

    /**
     * @var int
     *
     * @ORM\Column(name="manual", type="integer", nullable=false)
     */
    private int $manual;

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
     * @return float
     */
    public function getCapturedTotal(): float
    {
        return (float) $this->capturedTotal;
    }

    /**
     * @param float $amount
     * @return $this
     */
    public function setCapturedTotal(float $amount): self
    {
        $this->capturedTotal = $amount;

        return $this;
    }

    /**
     * @return float
     */
    public function getCancelledTotal(): float
    {
        return (float) $this->cancelledTotal;
    }

    /**
     * @param float $amount
     * @return $this
     */
    public function setCancelledTotal(float $amount): self
    {
        $this->cancelledTotal = $amount;

        return $this;
    }

    /**
     * @return float
     */
    public function getRefundedTotal(): float
    {
        return (float) $this->refundedTotal;
    }

    /**
     * @param float $amount
     * @return $this
     */
    public function setRefundedTotal(float $amount): self
    {
        $this->refundedTotal = $amount;

        return $this;
    }

    /**
     * @return bool
     */
    public function isManual(): bool
    {
        return (bool) $this->manual;
    }

    /**
     * @param bool $flag
     * @return $this
     */
    public function setManual(bool $flag): self
    {
        $this->manual = $flag ? 1 : 0;

        return $this;
    }
}
