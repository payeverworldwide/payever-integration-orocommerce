<?php

namespace Payever\Bundle\PaymentBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\BaseType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DownloadLogsButtonType extends BaseType
{
    const NAME = 'payever_download_logs';

    /**
     * {@inheritdoc}
     */
    public function getParent(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('auto_initialize', false);
        $resolver->setDefault('disabled', false);
    }
}
