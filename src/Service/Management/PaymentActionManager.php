<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Management;

use DateTime;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Payever\Bundle\PaymentBundle\Entity\Repository\PaymentActionRepository;
use Payever\Bundle\PaymentBundle\Service\Factory\PaymentActionFactory;
use Payever\Bundle\PaymentBundle\Entity\PaymentAction;

class PaymentActionManager
{
    /**
     * Action Types
     */
    const ACTION_SHIPPING_GOODS = 'shipping_goods';
    const ACTION_REFUND = 'refund';
    const ACTION_CANCEL = 'cancel';
    const ACTION_INVOICE = 'invoice';

    /**
     * Action Sources
     */
    const SOURCE_EXTERNAL = 'external';
    const SOURCE_INTERNAL = 'internal';
    const SOURCE_PSP = 'psp';

    /**
     * @var Registry
     */
    private Registry $doctrine;

    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;

    /**
     * @var PaymentActionFactory
     */
    private PaymentActionFactory $paymentActionFactory;

    public function __construct(
        Registry $doctrine,
        EntityManager $entityManager,
        PaymentActionFactory $paymentActionFactory
    ) {
        $this->doctrine = $doctrine;
        $this->entityManager = $entityManager;
        $this->paymentActionFactory = $paymentActionFactory;
    }

    /**
     * Loads a payment action by its identifier.
     *
     * @param string $identifier The identifier of the payment action.
     *
     * @return PaymentAction|null The payment action object, or null if not found.
     */
    public function loadByIdentifier(string $identifier): ?PaymentAction
    {
        return $this->getRepository()->findByIdentifier($identifier);
    }

    /**
     * Adds a payment action for an order.
     *
     * @param Order $order The order object.
     * @param string $type The type of the payment action.
     * @param string $source The source of the payment action.
     * @param float $amount The amount of the payment action.
     *
     * @return PaymentAction The newly created payment action.
     */
    public function addAction(Order $order, string $type, string $source, float $amount): PaymentAction
    {
        /** @var PaymentAction $paymentAction */
        $paymentAction = $this->paymentActionFactory->create();
        $paymentAction->setIdentifier(UUIDGenerator::v4())
            ->setOrderId($order->getId())
            ->setType($type)
            ->setSource($source)
            ->setAmount($amount)
            ->setCreatedAt(new DateTime())
            ->setUpdatedAt(new DateTime());

        $this->entityManager->persist($paymentAction);
        $this->entityManager->flush($paymentAction);

        return $paymentAction;
    }

    /**
     * Retrieves the repository for PaymentAction entities.
     *
     * @return PaymentActionRepository The repository object for PaymentAction entities.
     */
    private function getRepository(): PaymentActionRepository
    {
        return $this->doctrine
            ->getManagerForClass(PaymentAction::class)
            ->getRepository(PaymentAction::class);
    }
}
