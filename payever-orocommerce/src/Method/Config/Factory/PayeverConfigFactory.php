<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\Config\Factory;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Payever\Bundle\PaymentBundle\Entity\PayeverSettings;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfig;

class PayeverConfigFactory implements PayeverConfigFactoryInterface
{
    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @var IntegrationIdentifierGeneratorInterface
     */
    private $identifierGenerator;

    /**
     * @param LocalizationHelper $localizationHelper
     * @param IntegrationIdentifierGeneratorInterface $identifierGenerator
     */
    public function __construct(
        LocalizationHelper $localizationHelper,
        IntegrationIdentifierGeneratorInterface $identifierGenerator
    ) {
        $this->localizationHelper = $localizationHelper;
        $this->identifierGenerator = $identifierGenerator;
    }

    /**
     * {@inheritDoc}
     */
    public function create(PayeverSettings $settings)
    {
        $params = [];
        $channel = $settings->getChannel();

        $params[PayeverConfig::FIELD_LABEL] = $this->getLocalizedValue($settings->getLabels());
        $params[PayeverConfig::FIELD_SHORT_LABEL] = $this->getLocalizedValue($settings->getShortLabels());
        $params[PayeverConfig::FIELD_ADMIN_LABEL] = $channel->getName();
        $params[PayeverConfig::FIELD_PAYMENT_METHOD_IDENTIFIER] =
            $this->identifierGenerator->generateIdentifier($channel);

        $params[PayeverConfig::PAYMENT_METHOD] = $settings->getPaymentMethod();
        $params[PayeverConfig::VARIANT_ID] = $settings->getVariantId();
        $params[PayeverConfig::DESCRIPTION_OFFER] = $settings->getDescriptionOffer();
        $params[PayeverConfig::DESCRIPTION_FEE] = $settings->getDescriptionFee();
        $params[PayeverConfig::IS_REDIRECT_METHOD] = $settings->getIsRedirectMethod();
        $params[PayeverConfig::IS_SUBMIT_METHOD] = $settings->getIsSubmitMethod();
        $params[PayeverConfig::INSTRUCTION_TEXT] = $settings->getInstructionText();
        $params[PayeverConfig::THUMBNAIL] = $settings->getThumbnail();
        $params[PayeverConfig::ALLOWED_CURRENCIES] = $settings->getCurrencies();
        $params[PayeverConfig::ALLOWED_COUNTRIES] = $settings->getCountries();
        $params[PayeverConfig::SHIPPING_ADDRESS_ALLOWED] = $settings->getIsShippingAddressAllowed();
        $params[PayeverConfig::SHIPPING_ADDRESS_EQUALITY] = $settings->getIsShippingAddressEquality();
        $params[PayeverConfig::MAX] = $settings->getMax();
        $params[PayeverConfig::MIN] = $settings->getMin();
        $params[PayeverConfig::IS_ACCEPT_FEE] = $settings->getIsAcceptFee();
        $params[PayeverConfig::FIXED_FEE] = $settings->getFixedFee();
        $params[PayeverConfig::VARIABLE_FEE] = $settings->getVariableFee();

        return new PayeverConfig($params);
    }

    /**
     * @param Collection $values
     *
     * @return string
     */
    private function getLocalizedValue(Collection $values)
    {
        return (string) $this->localizationHelper->getLocalizedValue($values);
    }
}
