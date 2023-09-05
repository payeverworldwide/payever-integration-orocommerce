<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\AddressBundle\Provider\CountryProvider;
use Payever\Bundle\PaymentBundle\Entity\PayeverSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class PayeverSettingsType extends AbstractType
{
    public const BLOCK_PREFIX = 'payever_settings';

    private TranslatorInterface $translator;

    private CountryProvider $countryProvider;

    public function __construct(
        TranslatorInterface $translator,
        CountryProvider $countryProvider
    ) {
        $this->translator = $translator;
        $this->countryProvider = $countryProvider;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'labels',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'payever.settings.labels.label',
                    'tooltip'  => 'payever.settings.labels.tooltip',
                    'required' => true,
                    'entry_options'  => [
                        'constraints' => [new NotBlank()]
                    ]
                ]
            )
            ->add(
                'shortLabels',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'payever.settings.short_labels.label',
                    'tooltip'  => 'payever.settings.short_labels.tooltip',
                    'required' => true,
                    'entry_options'  => [
                        'constraints' => [new NotBlank()]
                    ]
                ]
            )
            ->add(
                'paymentMethod',
                TextType::class,
                [
                    'label' => 'payever.settings.payment_method.label',
                    'required' => true,
                    'constraints' => [new NotBlank()]
                ]
            )
            ->add(
                'variantId',
                TextType::class,
                [
                    'label' => 'payever.settings.variant_id.label',
                    'required' => false
                ]
            )
            ->add(
                'descriptionOffer',
                TextareaType::class,
                [
                    'label' => 'payever.settings.description_offer.label',
                    'required' => false
                ]
            )
            ->add(
                'descriptionFee',
                TextareaType::class,
                [
                    'label' => 'payever.settings.description_fee.label',
                    'required' => false
                ]
            )
            ->add(
                'isRedirectMethod',
                CheckboxType::class,
                [
                    'label' => 'payever.settings.is_redirect_method.label',
                    'required' => false
                ]
            )
            ->add(
                'isSubmitMethod',
                CheckboxType::class,
                [
                    'label' => 'payever.settings.is_submit_method.label',
                    'required' => false
                ]
            )
            ->add(
                'instructionText',
                TextareaType::class,
                [
                    'label' => 'payever.settings.instruction_text.label',
                    'required' => false
                ]
            )
            ->add(
                'thumbnail',
                UrlType::class,
                [
                    'label' => 'payever.settings.thumbnail.label',
                    'required' => false
                ]
            )
            ->add(
                'currencies',
                CurrencySelectionType::class,
                [
                    'label' => 'payever.settings.currencies.label',
                    'required' => true,
                    'multiple' => true,
                    'constraints' => [new NotBlank()]
                ]
            )
            ->add(
                'countries',
                ChoiceType::class,
                [
                    'label' => 'payever.settings.countries.label',
                    'choices' => $this->countryProvider->getCountryChoices(),
                    'multiple' => true,
                    'constraints' => [new NotBlank()]
                ]
            )
            ->add(
                'isShippingAddressAllowed',
                CheckboxType::class,
                [
                    'label' => 'payever.settings.is_shipping_address_allowed.label',
                    'required' => false
                ]
            )
            ->add(
                'isShippingAddressEquality',
                CheckboxType::class,
                [
                    'label' => 'payever.settings.is_shipping_address_equality.label',
                    'required' => false
                ]
            )
            ->add(
                'max',
                NumberType::class,
                [
                    'label' => 'payever.settings.max.label',
                    'required' => true,
                    'constraints' => [new NotBlank()]
                ]
            )
            ->add(
                'min',
                NumberType::class,
                [
                    'label' => 'payever.settings.min.label',
                    'required' => true,
                    'constraints' => [new NotBlank()]
                ]
            )
            ->add(
                'isAcceptFee',
                CheckboxType::class,
                [
                    'label' => 'payever.settings.is_accept_fee.label',
                    'required' => false
                ]
            )
            ->add(
                'fixedFee',
                NumberType::class,
                [
                    'label' => 'payever.settings.fixed_fee.label',
                    'required' => false,
                    'constraints' => [new NotBlank()]
                ]
            )
            ->add(
                'variableFee',
                NumberType::class,
                [
                    'label' => 'payever.settings.variable_fee.label',
                    'required' => false,
                    'constraints' => [new NotBlank()]
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => PayeverSettings::class,
                'allow_extra_fields' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }
}
