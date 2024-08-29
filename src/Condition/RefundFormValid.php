<?php

namespace Payever\Bundle\PaymentBundle\Condition;

use Payever\Bundle\PaymentBundle\Form\Entity\OrderPayment;
use Payever\Bundle\PaymentBundle\Form\Entity\OrderLineItem;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ExpressionInterface;

class RefundFormValid extends AbstractCondition
{
    const NAME = 'payever_refund_form_valid';

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
        /** @var OrderPayment $peRefund */
        $peRefund = $context->get('peRefund');
        if (!$peRefund) {
            return false;
        }

        /** @var OrderLineItem $item */
        foreach ($peRefund->getOrderLines() as $item) {
            $qty = $item->getQuantityToRefund() !== null ? $item->getQuantityToRefund() : 0;
            if (!preg_match("/^[\d]+$/", $qty)) {
                return false;
            }
        }

        return true;
    }
}
