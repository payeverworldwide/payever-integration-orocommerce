<?php

namespace Payever\Bundle\PaymentBundle\Condition;

use Payever\Bundle\PaymentBundle\Form\Entity\OrderPayment;
use Payever\Bundle\PaymentBundle\Form\Entity\OrderLineItem;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ExpressionInterface;

class ShipFormValid extends AbstractCondition
{
    const NAME = 'payever_ship_form_valid';

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
        /** @var OrderPayment $peShip */
        $peShip = $context->get('peShip');
        if (!$peShip) {
            return false;
        }

        /** @var OrderLineItem $item */
        foreach ($peShip->getOrderLines() as $item) {
            $qty = $item->getQuantityToCapture() !== null ? $item->getQuantityToCapture() : 0;
            if (!preg_match("/^[\d]+$/", $qty)) {
                return false;
            }
        }

        return true;
    }
}
