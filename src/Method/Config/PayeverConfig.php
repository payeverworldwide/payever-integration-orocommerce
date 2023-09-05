<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\Config;

use Oro\Bundle\PaymentBundle\Method\Config\ParameterBag\AbstractParameterBagPaymentConfig;

class PayeverConfig extends AbstractParameterBagPaymentConfig implements PayeverConfigInterface
{
    public const PAYMENT_METHOD = 'payment_method';
    public const VARIANT_ID = 'variant_id';
    public const DESCRIPTION_OFFER = 'description_offer';
    public const DESCRIPTION_FEE = 'description_fee';
    public const IS_REDIRECT_METHOD = 'is_redirect_method';
    public const IS_SUBMIT_METHOD = 'is_submit_method';
    public const INSTRUCTION_TEXT = 'instruction_text';
    public const THUMBNAIL = 'thumbnail';
    public const ALLOWED_CURRENCIES = 'currencies';
    public const ALLOWED_COUNTRIES = 'countries';
    public const SHIPPING_ADDRESS_ALLOWED = 'is_shipping_address_allowed';
    public const SHIPPING_ADDRESS_EQUALITY = 'is_shipping_address_equality';
    public const MAX = 'max';
    public const MIN = 'min';
    public const IS_ACCEPT_FEE = 'is_accept_fee';
    public const FIXED_FEE = 'fixed_fee';
    public const VARIABLE_FEE = 'variable_fee';

    public function getPaymentMethod(): string
    {
        return (string) $this->get(self::PAYMENT_METHOD);
    }

    public function getVariantId(): ?string
    {
        return (string) $this->get(self::VARIANT_ID);
    }

    public function getDescriptionOffer(): string
    {
        return (string) $this->get(self::DESCRIPTION_OFFER);
    }

    public function getDescriptionFee(): string
    {
        return (string) $this->get(self::DESCRIPTION_FEE);
    }

    public function getIsRedirectMethod(): bool
    {
        return (bool) $this->get(self::IS_REDIRECT_METHOD);
    }

    public function getIsSubmitMethod(): bool
    {
        return (bool) $this->get(self::IS_SUBMIT_METHOD);
    }

    public function getInstructionText(): string
    {
        return (string) $this->get(self::INSTRUCTION_TEXT);
    }

    public function getThumbnail(): string
    {
        return (string) $this->get(self::THUMBNAIL);
    }

    public function getAllowedCurrencies(): array
    {
        $result = (string) $this->get(self::ALLOWED_CURRENCIES);
        $result = json_decode($result);
        if (JSON_ERROR_NONE === json_last_error()) {
            return $result;
        }

        return [];
    }

    public function getAllowedCountries(): array
    {
        $result = (string) $this->get(self::ALLOWED_COUNTRIES);
        $result = json_decode($result);
        if (JSON_ERROR_NONE === json_last_error()) {
            return $result;
        }

        return [];
    }

    public function getShippingAddressAllowed(): bool
    {
        return (bool) $this->get(self::SHIPPING_ADDRESS_ALLOWED);
    }

    public function getShippingAddressEquality(): bool
    {
        return (bool) $this->get(self::SHIPPING_ADDRESS_EQUALITY);
    }

    public function getAllowedMaxAmount(): float
    {
        return (float) $this->get(self::MAX);
    }

    public function getAllowedMinAmount(): float
    {
        return (float) $this->get(self::MIN);
    }

    public function getIsAcceptFee(): bool
    {
        return (bool) $this->get(self::IS_ACCEPT_FEE);
    }

    public function getFixedFee(): float
    {
        return (float) $this->get(self::FIXED_FEE);
    }

    public function getVariableFee(): float
    {
        return (float) $this->get(self::VARIABLE_FEE);
    }
}
