<?php

namespace Payever\Bundle\PaymentBundle\Form\Entity;

class OrderPayment
{
    /**
     * @var OrderLineItem[]
     */
    private array $orderLines = [];

    /**
     * @var float|null
     */
    private ?float $total;

    /**
     * @var float|null
     */
    private ?float $totalCancelled;

    /**
     * @var float|null
     */
    private ?float $totalCaptured;

    /**
     * @var float|null
     */
    private ?float $totalRefunded;

    /**
     * @var string
     */
    private string $currency;

    /**
     * @var string
     */
    private string $currencySymbol;

    private ?string $shippingDate = null;

    private ?string $trackingNumber = null;

    private ?string $trackingUrl = null;

    /**
     * @return OrderLineItem[]
     */
    public function getOrderLines(): array
    {
        return $this->orderLines;
    }

    /**
     * @param OrderLineItem[] $orderLines
     *
     * @return $this
     */
    public function setOrderLines(array $orderLines): self
    {
        $this->orderLines = $orderLines;

        return $this;
    }

    /**
     * @return float
     */
    public function getTotal(): ?float
    {
        return $this->total;
    }

    /**
     * @param float $total
     */
    public function setTotal($total): self
    {
        $this->total = $total;

        return $this;
    }

    /**
     * @return float
     */
    public function getTotalCancelled(): ?float
    {
        return $this->totalCancelled;
    }

    public function setTotalCancelled($totalCancelled): self
    {
        $this->totalCancelled = $totalCancelled;

        return $this;
    }

    /**
     * @return float
     */
    public function getTotalCaptured(): ?float
    {
        return $this->totalCaptured;
    }

    public function setTotalCaptured($totalCaptured): self
    {
        $this->totalCaptured = $totalCaptured;

        return $this;
    }

    /**
     * @return float
     */
    public function getTotalRefunded(): ?float
    {
        return $this->totalRefunded;
    }

    /**
     * @param float $totalRefunded
     */
    public function setTotalRefunded($totalRefunded): self
    {
        $this->totalRefunded = $totalRefunded;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency($currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrencySymbol(): string
    {
        return $this->currencySymbol;
    }

    /**
     * @param string $currencySymbol
     */
    public function setCurrencySymbol($currencySymbol): self
    {
        $this->currencySymbol = $currencySymbol;

        return $this;
    }

    public function getShippingDate(): ?string
    {
        return $this->shippingDate;
    }

    public function setShippingDate(?string $date): self
    {
        $this->shippingDate = $date;

        return $this;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(?string $trackingNumber): self
    {
        $this->trackingNumber = $trackingNumber;

        return $this;
    }

    public function getTrackingUrl(): ?string
    {
        return $this->trackingUrl;
    }

    public function setTrackingUrl(?string $trackingUrl): self
    {
        $this->trackingUrl = $trackingUrl;

        return $this;
    }
}
