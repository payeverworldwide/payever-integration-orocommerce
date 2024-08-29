<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Helper;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;

class ShoppingListHelper
{
    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;

    /**
     * @var CurrentShoppingListManager
     */
    private CurrentShoppingListManager $shoppingListManager;

    /**
     * Constructor.
     *
     * @param EntityManager $entityManager
     * @param CurrentShoppingListManager $shoppingListManager
     */
    public function __construct(
        EntityManager $entityManager,
        CurrentShoppingListManager $shoppingListManager
    ) {
        $this->entityManager = $entityManager;
        $this->shoppingListManager = $shoppingListManager;
    }

    /**
     * Clear Shopping List.
     *
     * @return void
     */
    public function clear(): void
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->shoppingListManager->getForCurrentUser();
        if ($shoppingList) {
            $this->entityManager->remove($shoppingList);
            $this->entityManager->flush($shoppingList);
        }
    }
}
