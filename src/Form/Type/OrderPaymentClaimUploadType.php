<?php

namespace Payever\Bundle\PaymentBundle\Form\Type;

use Payever\Bundle\PaymentBundle\Form\Entity\OrderClaimUpload;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderPaymentClaimUploadType extends AbstractType
{
    const NAME = 'oro_order_claim_upload_widget';

    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'invoices',
                FileType::class,
                [
                    'label' => 'Invoice',
                    'required' => true,
                    'multiple' => true,
                    'attr' => [
                        'accept' => 'image/*, application/pdf',
                    ],
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => OrderClaimUpload::class,
                'validation_groups' => function (FormInterface $form) {
                    return ['Default'];
                },
                'attr' => ['class' => 'form-horizontal'],
            ]
        );
    }
}
