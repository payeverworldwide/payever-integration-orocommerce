<?php

namespace Payever\Bundle\PaymentBundle\Form\Type;

use Payever\Bundle\PaymentBundle\Form\Entity\OrderLineItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class OrderLineItemType extends AbstractType
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
            ->add('itemReference', HiddenType::class)
            ->add('itemType', HiddenType::class)
            ->add('name', HiddenType::class)
            ->add('unitPrice', HiddenType::class)
            ->add('totalPrice', HiddenType::class)
            ->add('quantity', HiddenType::class)
            ->add('qtyCaptured', HiddenType::class)
            ->add('qtyCancelled', HiddenType::class)
            ->add('qtyRefunded', HiddenType::class);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        $nodeName = $event->getForm()->getParent()->getParent()->getName();

        /** @var OrderLineItem|null $orderLineItem */
        $orderLineItem = $event->getData();
        if (!$orderLineItem) {
            return;
        }

        if ($orderLineItem->getQuantity() === null) {
            return;
        }

        switch ($nodeName) {
            case 'peRefund':
                // Add "Quantity To Refund" field
                $event->getForm()->add(
                    'quantityToRefund',
                    IntegerType::class,
                    [
                        'required' => false,
                        'label' => ' ',
                        'constraints' => [new Range([
                            'min' => 0,
                            'max' => $orderLineItem->getQuantity() - $orderLineItem->getQtyRefunded(),
                        ])],
                    ]
                );

                break;
            case 'peCancel':
                // Add "Quantity To Cancel" field
                $event->getForm()->add(
                    'quantityToCancel',
                    IntegerType::class,
                    [
                        'required' => false,
                        'label' => ' ',
                        'constraints' => [new Range([
                            'min' => 0,
                            'max' => $orderLineItem->getQuantity() - $orderLineItem->getQtyCancelled(),
                        ])],
                    ]
                );

                break;
            case 'peShip':
                // Add "Quantity To Capture" field
                $event->getForm()->add(
                    'quantityToCapture',
                    IntegerType::class,
                    [
                        'required' => false,
                        'label' => ' ',
                        'constraints' => [new Range([
                            'min' => 0,
                            'max' => $orderLineItem->getQuantity() - $orderLineItem->getQtyCancelled(),
                        ])],
                    ]
                );

                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => OrderLineItem::class,
                'validation_groups' => function (FormInterface $form) {
                    return ['Default'];
                }
            ]
        );
    }
}
