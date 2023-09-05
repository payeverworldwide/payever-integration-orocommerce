<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * Register all possible payment actions used in the application
 */
class PaymentActionRegistry
{
    private iterable $paymentActions;

    /**
     * @param iterable|PaymentActionInterface[] $paymentActions
     */
    public function __construct(iterable $paymentActions)
    {
        $this->paymentActions = $paymentActions;
    }

    /**
     * @param string $type
     * @param PaymentTransaction $transaction
     *
     * @return PaymentActionInterface
     */
    public function getPaymentAction(string $type, PaymentTransaction $transaction): PaymentActionInterface
    {
        foreach ($this->paymentActions as $paymentAction) {
            if ($paymentAction->isApplicable($type, $transaction)) {
                return $paymentAction;
            }
        }

        throw new \LogicException(sprintf('Action "%s" is not implemented yet.', $type));
    }
}
