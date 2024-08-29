<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Generator;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowStepRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Psr\Log\LoggerInterface;

/**
 *
 */
class CheckoutGenerator
{
    private const WORKFLOW_STEP_ORDER_CREATED = 'order_created';

    /**
     * @var ObjectManager
     */
    private ObjectManager $manager;

    /**
     * @var ManagerRegistry
     */
    private ManagerRegistry $managerRegistry;

    /**
     * @var PaymentTransactionProvider
     */
    private PaymentTransactionProvider $paymentTransactionProvider;

    /**
     * @var WorkflowManager
     */
    private WorkflowManager $workflowManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Constructor.
     *
     * @param ObjectManager $manager
     * @param ManagerRegistry $managerRegistry
     * @param PaymentTransactionProvider $paymentTransactionProvider
     * @param WorkflowManager $workflowManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        ObjectManager $manager,
        ManagerRegistry $managerRegistry,
        PaymentTransactionProvider $paymentTransactionProvider,
        WorkflowManager $workflowManager,
        LoggerInterface $logger
    ) {
        $this->manager = $manager;
        $this->managerRegistry = $managerRegistry;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
        $this->workflowManager = $workflowManager;
        $this->logger = $logger;
    }

    /**
     * Create Checkout.
     *
     * @param Order $order
     * @return Checkout
     */
    public function createCheckout(Order $order): Checkout
    {
        $paymentTransaction = $this->paymentTransactionProvider->getPaymentTransaction($order);

        $checkout = $this->getCheckoutByBillingAddress($order->getBillingAddress());
        if (!$checkout) {
            $checkout = new Checkout();
        }

        $checkout->setSource(new CheckoutSource())
            ->setWebsite($order->getWebsite())
            ->setCustomerUser($order->getCustomerUser())
            ->setCustomer($order->getCustomer())
            ->setOrganization($order->getOrganization())
            ->setOwner($order->getOwner())
            ->setBillingAddress($order->getBillingAddress())
            ->setShippingAddress($order->getShippingAddress())
            ->setCurrency($order->getCurrency())
            ->setCreatedAt($order->getCreatedAt())
            ->setUpdatedAt($order->getUpdatedAt())
            ->setShippingCost($order->getShippingCost())
            ->setPaymentMethod($paymentTransaction->getPaymentMethod())
            ->setShippingMethod($order->getShippingMethod())
            ->setShippingMethodType('primary')
            ->setCompleted(true);

        $orderLineItems = $order->getLineItems();
        foreach ($orderLineItems as $lineItem) {
            $checkoutLineItem = new CheckoutLineItem();
            $checkoutLineItem->setProduct($lineItem->getProduct())
                ->setProductUnit($lineItem->getProductUnit())
                ->setPrice($lineItem->getPrice())
                ->setPriceType($lineItem->getPriceType())
                ->setQuantity($lineItem->getQuantity())
                ->setCurrency($lineItem->getCurrency())
                ->setParentProduct($lineItem->getParentProduct())
                ->setProductSku($lineItem->getProductSku());

            $checkout->addLineItem($checkoutLineItem);
        }

        $this->manager->persist($checkout);
        $this->manager->flush($checkout);

        try {
            $workFlowItem = $this->workflowManager->startWorkflow('b2b_flow_checkout', $checkout);
            if (!$workFlowItem) {
                throw new \LogicException('Workflow can not be started.');
            }

            $workFlowStep = $this->getCheckoutWorkflowStepRepository()->findOneBy(
                ['name' => self::WORKFLOW_STEP_ORDER_CREATED]
            );

            $workFlowData = $workFlowItem->getData();
            $workFlowData->add(['order' => $order]);

            $workFlowItem->setCurrentStep($workFlowStep)
                ->setData($workFlowData);

            $this->manager->persist($workFlowItem);
            $this->manager->flush($workFlowItem);
        } catch (WorkflowException $exception) {
            // Silence is golden
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            $this->logger->critical($exception->getTraceAsString());
        }

        return $checkout;
    }

    /**
     * Get Checkout by billing address.
     *
     * @param OrderAddress $address
     * @return Checkout|null
     */
    private function getCheckoutByBillingAddress(OrderAddress $address): ?Checkout
    {
        /** @var CheckoutRepository $repository */
        $repository = $this->managerRegistry
            ->getManagerForClass(Checkout::class)
            ->getRepository(Checkout::class);

        return $repository->findOneBy(['billingAddress' => $address]);
    }

    /**
     * @return WorkflowStepRepository
     */
    private function getCheckoutWorkflowStepRepository(): WorkflowStepRepository
    {
        return $this->managerRegistry
            ->getManagerForClass(WorkflowStep::class)
            ->getRepository(WorkflowStep::class);
    }
}
