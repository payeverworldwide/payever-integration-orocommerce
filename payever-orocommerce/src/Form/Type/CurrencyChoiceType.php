<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyListProviderInterface;
use Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CurrencyChoiceType extends ChoiceType
{
    /** @var CurrencyListProviderInterface  */
    protected CurrencyListProviderInterface $currencyProvider;

    public function __construct(
        ChoiceListFactoryInterface $choiceListFactory,
        $translator,
        CurrencyListProviderInterface $currencyProvider
    ) {
        parent::__construct($choiceListFactory, $translator);

        $this->currencyProvider = $currencyProvider;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        return parent::configureOptions($resolver);
    }
}
