<?php

namespace Payever\Bundle\PaymentBundle\Form\Type;

use Payever\Bundle\PaymentBundle\Form\Entity\OrderPayment;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

trait OrderPaymentTrait
{
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
            ]);
    }

    /**
     * {@inheritdoc}
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['required'] = false;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => OrderPayment::class,
                'show_form_when_empty' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
