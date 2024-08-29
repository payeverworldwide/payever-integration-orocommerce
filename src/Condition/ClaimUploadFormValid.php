<?php

namespace Payever\Bundle\PaymentBundle\Condition;

use Payever\Bundle\PaymentBundle\Form\Entity\OrderClaim;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ExpressionInterface;
use Payever\Bundle\PaymentBundle\Form\Entity\OrderClaimUpload;

class ClaimUploadFormValid extends AbstractCondition
{
    const NAME = 'payever_claim_form_valid';

    /**
     * @return string
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * @param array $options
     * @return $this|ExpressionInterface
     */
    public function initialize(array $options)
    {
        reset($options);

        return $this;
    }

    /**
     * Validates input
     *
     * @param mixed $context
     *
     * @return bool
     */
    protected function isConditionAllowed($context)
    {
        /** @var OrderClaimUpload $peClaim */
        $peClaimUpload = $context->get('peClaimUpload');
        if (!$peClaimUpload) {
            return false;
        }

        if (!$peClaimUpload->getInvoices()) {
            return false;
        }

        return true;
    }
}
