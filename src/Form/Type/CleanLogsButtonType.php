<?php

namespace Payever\Bundle\PaymentBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\BaseType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CleanLogsButtonType extends BaseType
{
    const NAME = 'payever_clean_logs';

    /**
     * {@inheritdoc}
     */
    public function getParent()
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
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['errors'] = [];
        $view->vars['multipart'] = false;
        $view->vars['id'] = self::NAME;
        $view->vars['unique_block_prefix'] = self::NAME;
        $view->vars['block_prefixes'] = [self::NAME];
        $view->vars['cache_key'] = self::NAME;
        $view->vars[-1] = self::NAME;
        $view->vars['full_name'] = self::NAME;
        $view->vars['disabled'] = false;
        $view->vars['label'] = false;
        $view->vars['translation_domain'] = false;
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
