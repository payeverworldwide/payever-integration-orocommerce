<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfig;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Payever\Bundle\PaymentBundle\Entity\Repository\PayeverSettingsRepository")
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class PayeverSettings extends Transport
{
    /**
     * @var string
     * @ORM\Column(name="payever_payment_method", type="string", nullable=true)
     */
    protected $paymentMethod;

    /**
     * @var string
     * @ORM\Column(name="payever_variant_id", type="string", nullable=true)
     */
    protected $variantId;

    /**
     * @var string
     * @ORM\Column(name="payever_description_offer", type="string", nullable=true)
     */
    protected $descriptionOffer;

    /**
     * @var string
     * @ORM\Column(name="payever_description_fee", type="string", nullable=true)
     */
    protected $descriptionFee;

    /**
     * @var bool
     * @ORM\Column(name="payever_is_redirect_method", type="boolean", options={"default"=false})
     */
    protected $isRedirectMethod = false;

    /**
     * @var bool
     * @ORM\Column(name="payever_is_submit_method", type="boolean", options={"default"=false})
     */
    protected $isSubmitMethod = false;

    /**
     * @var string
     * @ORM\Column(name="payever_instruction_text", type="string", nullable=true)
     */
    protected $instructionText;

    /**
     * @var string
     * @ORM\Column(name="payever_thumbnail", type="string", nullable=true)
     */
    protected $thumbnail;

    /**
     * @var string
     * @ORM\Column(name="payever_currencies", type="string", nullable=true)
     */
    protected $currencies;

    /**
     * @var string
     * @ORM\Column(name="payever_countries", type="string", nullable=true)
     */
    protected $countries;

    /**
     * @var bool
     * @ORM\Column(name="payever_is_shipping_address_allowed", type="boolean", options={"default"=false})
     */
    protected $isShippingAddressAllowed = false;

    /**
     * @var bool
     * @ORM\Column(name="payever_is_shipping_address_equality", type="boolean", options={"default"=false})
     */
    protected $isShippingAddressEquality = false;

    /**
     * @var float
     *
     * @ORM\Column(name="payever_max", type="float", nullable=true)
     */
    protected $max;

    /**
     * @var float
     *
     * @ORM\Column(name="payever_min", type="float", nullable=true)
     */
    protected $min;

    /**
     * @var bool
     * @ORM\Column(name="payever_is_accept_fee", type="boolean", options={"default"=false})
     */
    protected $isAcceptFee = false;

    /**
     * @var float
     *
     * @ORM\Column(name="payever_fixed_fee", type="float", nullable=true)
     */
    protected $fixedFee;

    /**
     * @var float
     *
     * @ORM\Column(name="payever_variable_fee", type="float", nullable=true)
     */
    protected $variableFee;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="payever_trans_label",
     *      joinColumns={
     *          @ORM\JoinColumn(name="transport_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @Assert\NotBlank
     */
    private $labels;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="payever_short_label",
     *      joinColumns={
     *          @ORM\JoinColumn(name="transport_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @Assert\NotBlank
     */
    private $shortLabels;

    /**
     * @var ParameterBag
     */
    private $settings;


    public function __construct()
    {
        $this->labels = new ArrayCollection();
        $this->shortLabels = new ArrayCollection();
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * @param LocalizedFallbackValue $label
     *
     * @return $this
     */
    public function addLabel(LocalizedFallbackValue $label)
    {
        if (!$this->labels->contains($label)) {
            $this->labels->add($label);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $label
     *
     * @return $this
     */
    public function removeLabel(LocalizedFallbackValue $label)
    {
        if ($this->labels->contains($label)) {
            $this->labels->removeElement($label);
        }

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getShortLabels()
    {
        return $this->shortLabels;
    }

    /**
     * @param LocalizedFallbackValue $label
     *
     * @return $this
     */
    public function addShortLabel(LocalizedFallbackValue $label)
    {
        if (!$this->shortLabels->contains($label)) {
            $this->shortLabels->add($label);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $label
     *
     * @return $this
     */
    public function removeShortLabel(LocalizedFallbackValue $label)
    {
        if ($this->shortLabels->contains($label)) {
            $this->shortLabels->removeElement($label);
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    /**
     * @param string|null $paymentMethod
     *
     * @return $this
     */
    public function setPaymentMethod(?string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getVariantId(): ?string
    {
        return $this->variantId;
    }

    /**
     * @param string|null $variantId
     *
     * @return $this
     */
    public function setVariantId(?string $variantId): self
    {
        $this->variantId = $variantId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescriptionOffer(): ?string
    {
        return $this->descriptionOffer;
    }

    /**
     * @param string|null $descriptionOffer
     *
     * @return $this
     */
    public function setDescriptionOffer(?string $descriptionOffer): self
    {
        $this->descriptionOffer = $descriptionOffer;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescriptionFee(): ?string
    {
        return $this->descriptionFee;
    }

    /**
     * @param string|null $descriptionFee
     *
     * @return $this
     */
    public function setDescriptionFee(?string $descriptionFee): self
    {
        $this->descriptionFee = $descriptionFee;

        return $this;
    }

    public function getIsRedirectMethod(): bool
    {
        return $this->isRedirectMethod;
    }

    public function setIsRedirectMethod(bool $isRedirectMethod): self
    {
        $this->isRedirectMethod = $isRedirectMethod;

        return $this;
    }

    public function getIsSubmitMethod(): bool
    {
        return $this->isSubmitMethod;
    }

    public function setIsSubmitMethod(bool $isSubmitMethod): self
    {
        $this->isSubmitMethod = $isSubmitMethod;

        return $this;
    }

    public function getInstructionText(): ?string
    {
        return $this->instructionText;
    }

    public function setInstructionText(?string $instructionText): self
    {
        $this->instructionText = $instructionText;

        return $this;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(?string $thumbnail): self
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    public function getCurrencies(): array
    {
        $currencies = json_decode((string) $this->currencies, true);
        if (JSON_ERROR_NONE === json_last_error()) {
            return $currencies;
        }

        return [];
    }

    public function setCurrencies(array $currencies): self
    {
        $this->currencies = json_encode($currencies);

        return $this;
    }

    public function getCountries(): array
    {
        $countries = json_decode((string) $this->countries, true);
        if (JSON_ERROR_NONE === json_last_error()) {
            return $countries;
        }

        return [];
    }

    public function setCountries(array $countries): self
    {
        $this->countries = json_encode($countries);

        return $this;
    }

    public function getIsShippingAddressAllowed(): bool
    {
        return $this->isShippingAddressAllowed;
    }

    public function setIsShippingAddressAllowed(bool $isShippingAddressAllowed): self
    {
        $this->isShippingAddressAllowed = $isShippingAddressAllowed;

        return $this;
    }

    public function getIsShippingAddressEquality(): bool
    {
        return $this->isShippingAddressEquality;
    }

    public function setIsShippingAddressEquality(bool $isShippingAddressEquality): self
    {
        $this->isShippingAddressEquality = $isShippingAddressEquality;

        return $this;
    }

    public function getMax(): float
    {
        return (float) $this->max;
    }

    public function setMax(float $max): self
    {
        $this->max = $max;

        return $this;
    }

    public function getMin(): float
    {
        return (float) $this->min;
    }

    public function setMin(float $min): self
    {
        $this->min = $min;

        return $this;
    }

    public function getIsAcceptFee(): bool
    {
        return $this->isAcceptFee;
    }

    public function setIsAcceptFee(bool $isAcceptFee): self
    {
        $this->isAcceptFee = $isAcceptFee;

        return $this;
    }

    public function getVariableFee(): float
    {
        return (float) $this->variableFee;
    }

    public function setVariableFee(float $variableFee): self
    {
        $this->variableFee = $variableFee;

        return $this;
    }

    public function getFixedFee(): float
    {
        return (float) $this->fixedFee;
    }

    public function setFixedFee(float $fixedFee): self
    {
        $this->fixedFee = $fixedFee;

        return $this;
    }

    /**
     * @return ParameterBag
     */
    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag(
                [
                    'labels' => $this->getLabels(),
                    'short_labels' => $this->getShortLabels(),
                    PayeverConfig::PAYMENT_METHOD => $this->getPaymentMethod(),
                    PayeverConfig::VARIANT_ID => $this->getVariantId(),
                    PayeverConfig::DESCRIPTION_OFFER => $this->getDescriptionOffer(),
                    PayeverConfig::DESCRIPTION_FEE => $this->getDescriptionFee(),
                    PayeverConfig::IS_REDIRECT_METHOD => $this->getIsRedirectMethod(),
                    PayeverConfig::IS_SUBMIT_METHOD => $this->getIsSubmitMethod(),
                    PayeverConfig::INSTRUCTION_TEXT => $this->getInstructionText(),
                    PayeverConfig::THUMBNAIL => $this->getThumbnail(),
                    PayeverConfig::ALLOWED_CURRENCIES => $this->getCurrencies(),
                    PayeverConfig::ALLOWED_COUNTRIES => $this->getCountries(),
                    PayeverConfig::SHIPPING_ADDRESS_ALLOWED => $this->getIsShippingAddressAllowed(),
                    PayeverConfig::SHIPPING_ADDRESS_EQUALITY => $this->getIsShippingAddressEquality(),
                    PayeverConfig::MAX => $this->getMax(),
                    PayeverConfig::MIN => $this->getMin(),
                    PayeverConfig::IS_ACCEPT_FEE => $this->getIsAcceptFee(),
                    PayeverConfig::FIXED_FEE => $this->getFixedFee(),
                    PayeverConfig::VARIABLE_FEE => $this->getVariableFee(),
                ]
            );
        }

        return $this->settings;
    }
}
