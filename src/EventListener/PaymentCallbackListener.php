<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\EventListener;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Payever\Bundle\PaymentBundle\Constant\QueryConstant;
use Payever\Bundle\PaymentBundle\Method\Payever;
use Payever\Bundle\PaymentBundle\Service\Payment\Notification\NotificationRequestProcessor;
use Payever\Bundle\PaymentBundle\Service\Payment\PaymentProcessorService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class PaymentCallbackListener
{
    /**
     * @var PaymentMethodProviderInterface
     */
    private PaymentMethodProviderInterface $paymentMethodProvider;

    /**
     * @var PaymentProcessorService
     */
    private PaymentProcessorService $paymentProcessor;

    /**
     * @var NotificationRequestProcessor
     */
    private NotificationRequestProcessor $notificationRequestProcessor;

    /**
     * @var RequestStack
     */
    private RequestStack $requestStack;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct(
        PaymentMethodProviderInterface $paymentMethodProvider,
        PaymentProcessorService $paymentProcessor,
        NotificationRequestProcessor $notificationRequestProcessor,
        RequestStack $requestStack,
        LoggerInterface $logger,
    ) {
        $this->paymentMethodProvider = $paymentMethodProvider;
        $this->paymentProcessor = $paymentProcessor;
        $this->notificationRequestProcessor = $notificationRequestProcessor;
        $this->requestStack = $requestStack;
        $this->logger = $logger;
    }

    /**
     * @param AbstractCallbackEvent $event
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \Throwable
     */
    public function onReturn(AbstractCallbackEvent $event): void
    {
        $this->logger->debug(__METHOD__);

        $paymentTransaction = $event->getPaymentTransaction();
        if (!$paymentTransaction) {
            $this->logger->error('No payment transaction found onReturn event');

            return;
        }

        /** @var Payever $paymentMethod */
        $paymentMethod = $this->paymentMethodProvider->getPaymentMethod($paymentTransaction->getPaymentMethod());
        if (!$paymentMethod) {
            $this->logger->error('No payment method found onReturn event');
            $this->redirectToFailureUrl($paymentTransaction, $event);

            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $paymentId = $request->get(QueryConstant::PARAMETER_PAYMENT_ID);

        if (!$paymentId || QueryConstant::PAYMENT_ID_PLACEHODLER === $paymentId) {
            $this->logger->info(
                'Payment ID is invalid.',
                [$paymentTransaction->getEntityIdentifier()]
            );

            return;
        }

        $this->logger->info(
            'Payment handling.',
            [$paymentTransaction->getEntityIdentifier(), $paymentId]
        );

        $this->paymentProcessor->finalizePayment($paymentTransaction, $paymentId);
        $this->logger->debug('Payment has been finalized');

        $event->markSuccessful();
    }

    /**
     * @param AbstractCallbackEvent $event
     */
    public function onNotify(AbstractCallbackEvent $event): void
    {
        $this->logger->debug(__METHOD__);
        $paymentTransaction = $event->getPaymentTransaction();
        if (!$paymentTransaction) {
            $this->logger->error('No payment transaction found onNotify event');

            return;
        }

        /** @var Payever $paymentMethod */
        $paymentMethod = $this->paymentMethodProvider->getPaymentMethod($paymentTransaction->getPaymentMethod());
        if (!$paymentMethod) {
            $this->logger->error('No payment method found onNotify event');

            return;
        }

        $payload = $this->requestStack->getCurrentRequest()->getContent();
        $this->logger->info('[Notification] Notice callback hit for payment', [$paymentTransaction->getId()]);
        $this->logger->info('[Notification] Payload: ' . $payload);

        $this->notificationRequestProcessor->processNotification($payload);

        $event->markSuccessful();
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @param AbstractCallbackEvent $event
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function redirectToFailureUrl(
        PaymentTransaction $paymentTransaction,
        AbstractCallbackEvent $event
    ): void {
        $event->stopPropagation();

        $transactionOptions = $paymentTransaction->getTransactionOptions();
        if (!empty($transactionOptions['failureUrl'])) {
            $event->setResponse(new RedirectResponse($transactionOptions['failureUrl']));

            return;
        }
        $event->markFailed();
    }
}
