<?php

namespace Payever\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Payever\Bundle\PaymentBundle\Entity\PaymentAction;

class PaymentActionRepository extends ServiceEntityRepository
{
    /**
     * Finds a payment action by its identifier.
     *
     * @param string $identifier The identifier of the payment action.
     *
     * @return PaymentAction|null The payment action with the given identifier, or null if not found.
     */
    public function findByIdentifier(string $identifier): ?PaymentAction
    {
        return $this->findOneBy(['identifier' => $identifier]);
    }
}
