<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Management;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutToOrderConverter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Payever\Bundle\PaymentBundle\Constant\QueryConstant;
use Payever\Bundle\PaymentBundle\Entity\PayeverCheckout;
use Payever\Bundle\PaymentBundle\Method\Config\Provider\PayeverConfigProviderInterface;
use Payever\Bundle\PaymentBundle\Service\Payment\PaymentProcessorService;

/**
 * Class CheckoutManager
 */
class CheckoutManager
{
    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;

    /**
     * @var PaymentProcessorService
     */
    private PaymentProcessorService $paymentProcessorService;

    /**
     * @var PayeverConfigProviderInterface
     */
    private PayeverConfigProviderInterface $payeverConfigProvider;

    /**
     * @var WorkflowManager
     */
    private WorkflowManager $workflowManager;

    /**
     * @var WorkflowRegistry
     */
    private WorkflowRegistry $workflowRegistry;

    /**
     * @var CheckoutToOrderConverter
     */
    private CheckoutToOrderConverter $checkoutToOrderConverter;

    /**
     * @param EntityManager $entityManager
     * @param PaymentProcessorService $paymentProcessorService
     * @param PayeverConfigProviderInterface $payeverConfigProvider
     * @param WorkflowManager $workflowManager
     * @param WorkflowRegistry $workflowRegistry
     * @param CheckoutToOrderConverter $checkoutToOrderConverter
     */
    public function __construct(
        EntityManager $entityManager,
        PaymentProcessorService $paymentProcessorService,
        PayeverConfigProviderInterface $payeverConfigProvider,
        WorkflowManager $workflowManager,
        WorkflowRegistry $workflowRegistry,
        CheckoutToOrderConverter $checkoutToOrderConverter,
    ) {
        $this->entityManager = $entityManager;
        $this->paymentProcessorService = $paymentProcessorService;
        $this->payeverConfigProvider = $payeverConfigProvider;
        $this->workflowManager = $workflowManager;
        $this->workflowRegistry = $workflowRegistry;
        $this->checkoutToOrderConverter = $checkoutToOrderConverter;
    }

    /**
     * @param Checkout $checkout
     *
     * @return string
     *
     * @throws \Exception|\Throwable
     */
    public function initiateCheckoutPayment(Checkout $checkout): string
    {
        if (!$checkout->getPaymentMethod()) {
            throw new \LogicException('Payment method is not found.');
        }

        $config = $this->payeverConfigProvider->getPaymentConfig($checkout->getPaymentMethod());
        if (!$config) {
            throw new \LogicException('Payment method config is not found.');
        }

        $workflowItem = $this->workflowManager->getWorkflowItem($checkout, 'b2b_flow_checkout');

        // Check order validation
        $workflow = $this->workflowRegistry->getWorkflow($workflowItem->getWorkflowName());
        if (!$workflow?->isTransitionAllowed($workflowItem, 'place_order')) {
            throw new \LogicException('Submit order is not allowed.');
        }

        //Convert checkout to order data
        $order = $this->checkoutToOrderConverter->getOrder($checkout);

        return $this->paymentProcessorService->getPaymentUrl($order, $checkout, $config);
    }

    /**
     * @param Checkout $checkout
     * @param string $paymentId
     *
     * @return PayeverCheckout
     *
     * @throws ForbiddenTransitionException
     * @throws InvalidTransitionException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws WorkflowException
     * @throws WorkflowNotFoundException
     */
    public function completeCheckoutPayment(Checkout $checkout, string $paymentId): PayeverCheckout
    {
        // Check if order was already created
        $payeverCheckout = $this->preparePayeverCheckout($checkout->getId(), $paymentId);
        if ($payeverCheckout->getOrderId()) {
            return $payeverCheckout;
        }

        $workflowItem = $this->workflowManager->getWorkflowItem($checkout, 'b2b_flow_checkout');

         // Check order validation
        $workflow = $this->workflowRegistry->getWorkflow($workflowItem->getWorkflowName());
        if (!$workflow?->isTransitionAllowed($workflowItem, 'place_order')) {
            throw new \LogicException('Place order is not allowed.');
        }

        // Process oro order creation
        $this->workflowManager->transit($workflowItem, 'place_order');

        $data = $workflowItem->getResult()->get('responseData');
        $data['returnUrl'] .= '?'. http_build_query([QueryConstant::PARAMETER_PAYMENT_ID => $paymentId]);

        $payeverCheckout->setOrderId($checkout->getOrder()->getId());
        $payeverCheckout->setData($data);

        $this->entityManager->persist($payeverCheckout);
        $this->entityManager->flush();

        return $payeverCheckout;
    }

    /**
     * @param int $checkoutId
     *
     * @return Checkout|null
     *
     * @throws NotSupported
     */
    public function getOroCheckout(int $checkoutId):? Checkout
    {
        $repo = $this->entityManager->getRepository(Checkout::class);

        return $repo->findOneBy(['id' => $checkoutId]);
    }

    /**
     * @param int $checkoutId
     *
     * @return PayeverCheckout|null
     *
     * @throws NotSupported
     */
    public function getPayeverCheckout(int $checkoutId):? PayeverCheckout
    {
        $repo = $this->entityManager->getRepository(PayeverCheckout::class);

        return $repo->findOneBy(['checkoutId' => $checkoutId]);
    }

    /**
     * @param int $checkoutId
     * @param string $paymentId
     *
     * @return PayeverCheckout
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function preparePayeverCheckout(int $checkoutId, string $paymentId): PayeverCheckout
    {
        $payeverCheckout = $this->getPayeverCheckout($checkoutId);
        if (!$payeverCheckout) {
            $payeverCheckout = new PayeverCheckout();
            $payeverCheckout->setCheckoutId($checkoutId);
            $payeverCheckout->setPaymentId($paymentId);

            $this->entityManager->persist($payeverCheckout);
            $this->entityManager->flush();
        }

        return $payeverCheckout;
    }
}
