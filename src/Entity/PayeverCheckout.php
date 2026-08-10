<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;

#[ORM\Entity]
#[ORM\Table(name: 'payever_payment_checkout')]
class PayeverCheckout
{
    use DatesAwareTrait;

    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(name: 'order_id', type: Types::INTEGER, nullable: true)]
    private ?int $orderId = null;

    #[ORM\Column(name: 'checkout_id', type: Types::INTEGER, nullable: false)]
    private int $checkoutId;

    #[ORM\Column(name: 'payment_id', type: Types::STRING, length: 64, nullable: false)]
    private string $paymentId;

    #[ORM\Column(name: 'data', type: Types::ARRAY, nullable: true)]
    private ?array $data = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->updatedAt = new \DateTime();
        if (!$this->createdAt) {
            $this->createdAt = new \DateTime();
        }
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getOrderId():? int
    {
        return $this->orderId;
    }

    /**
     * @param int $orderId
     *
     * @return $this
     */
    public function setOrderId(int $orderId): PayeverCheckout
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * @return int
     */
    public function getCheckoutId(): int
    {
        return $this->checkoutId;
    }

    /**
     * @param int $checkoutId
     *
     * @return $this
     */
    public function setCheckoutId(int $checkoutId): PayeverCheckout
    {
        $this->checkoutId = $checkoutId;

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
     *
     * @return $this
     */
    public function setPaymentId(string $paymentId): PayeverCheckout
    {
        $this->paymentId = $paymentId;

        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function setData(array $data): PayeverCheckout
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getReturnUrl():? string
    {
        return $this->data['returnUrl'] ?? null;
    }
}
