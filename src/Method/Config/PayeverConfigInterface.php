<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\Config;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;

interface PayeverConfigInterface extends PaymentConfigInterface
{
    public function getPaymentMethod(): string;

    public function getVariantId(): ?string;

    public function getDescriptionOffer(): string;

    public function getDescriptionFee(): string;

    public function getIsRedirectMethod(): bool;

    public function getIsSubmitMethod(): bool;

    public function getIsB2BMethod(): bool;

    public function getInstructionText(): string;

    public function getThumbnail(): string;

    public function getAllowedCurrencies(): array;

    public function getAllowedCountries(): array;

    public function getShippingAddressAllowed(): bool;

    public function getShippingAddressEquality(): bool;

    public function getAllowedMaxAmount(): float;

    public function getAllowedMinAmount(): float;

    public function getIsAcceptFee(): bool;

    public function getFixedFee(): float;

    public function getVariableFee(): float;
}
