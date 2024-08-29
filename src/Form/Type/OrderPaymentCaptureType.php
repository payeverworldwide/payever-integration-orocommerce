<?php

namespace Payever\Bundle\PaymentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

class OrderPaymentCaptureType extends AbstractType
{
    use OrderPaymentTrait;

    const NAME = 'oro_order_capture_widget';

    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('total', HiddenType::class)
            ->add('totalCancelled', HiddenType::class)
            ->add('totalCaptured', HiddenType::class)
            ->add('totalRefunded', HiddenType::class)
            ->add('currency', HiddenType::class)
            ->add('currencySymbol', HiddenType::class)
            ->add('orderLines', CollectionType::class, [
                'entry_type' => OrderLineItemType::class,
                'entry_options' => ['label' => false],
                'label' =>  false,
                'by_reference' => false,
                'required' => false,
            ])
            ->add(
                'trackingNumber',
                TextType::class,
                [
                    'required' => false,
                    'attr' => ['class' => 'form-control']
                ]
            )
            ->add(
                'trackingUrl',
                UrlType::class,
                [
                    'required' => false,
                    'attr' => ['class' => 'form-control']
                ]
            )
            ->add(
                'shippingDate',
                TextType::class,
                [
                    'required' => false,
                    'attr' => ['class' => 'form-control', 'placeholder' => 'YYYY-MM-DD']
                ]
            );
    }
}
