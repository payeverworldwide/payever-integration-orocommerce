<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\EventListener;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Payever\Bundle\PaymentBundle\Constant\QueryConstant;
use Payever\Bundle\PaymentBundle\Method\Config\Provider\PayeverConfigProviderInterface;
use Payever\Bundle\PaymentBundle\Method\Payever;
use Payever\Bundle\PaymentBundle\Service\Payment\PaymentProcessorService;
use Payever\Bundle\PaymentBundle\Service\Payment\Notification\NotificationRequestProcessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class PaymentCallbackListener
{
    /**
     * @var PaymentMethodProviderInterface
     */
    private PaymentMethodProviderInterface $paymentMethodProvider;

    private PayeverConfigProviderInterface $payeverConfigProvider;

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
    private RequestStack $request;

    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    private \Symfony\Component\HttpFoundation\RequestStack $session;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    public function __construct(
        PaymentMethodProviderInterface $paymentMethodProvider,
        PayeverConfigProviderInterface $payeverConfigProvider,
        PaymentProcessorService $paymentProcessor,
        NotificationRequestProcessor $notificationRequestProcessor,
        RequestStack $request,
        \Symfony\Component\HttpFoundation\RequestStack $session,
        LoggerInterface $logger
    ) {
        $this->paymentMethodProvider = $paymentMethodProvider;
        $this->payeverConfigProvider = $payeverConfigProvider;
        $this->paymentProcessor = $paymentProcessor;
        $this->notificationRequestProcessor = $notificationRequestProcessor;
        $this->request = $request;
        $this->session = $session;
        $this->logger = $logger;
    }

    /**
     * @param AbstractCallbackEvent $event
     */
    public function onError(AbstractCallbackEvent $event): void
    {
        $this->logger->debug(__METHOD__);
        $paymentTransaction = $event->getPaymentTransaction();
        if (!$paymentTransaction) {
            $this->logger->error('No payment transaction found onError event');

            return;
        }

        /** @var Payever $paymentMethod */
        $paymentMethod = $this->paymentMethodProvider->getPaymentMethod($paymentTransaction->getPaymentMethod());
        if (!$paymentMethod) {
            $this->logger->error('No payment method found onError event');

            return;
        }

        $paymentTransaction
            ->setSuccessful(false)
            ->setActive(false);

        $request = $this->request->getCurrentRequest();
        if (QueryConstant::CALLBACK_TYPE_CANCEL === $request->get(QueryConstant::PARAMETER_TYPE)) {
            $this->logger->info(
                'Payment has been cancelled by customer.',
                [$paymentTransaction->getEntityIdentifier()]
            );

            $this->session->getFlashBag()->add(
                'warning',
                'payever.errors.payment_cancelled'
            );
        }

        if (QueryConstant::CALLBACK_TYPE_FAILURE === $request->get(QueryConstant::PARAMETER_TYPE)) {
            $this->logger->info(
                'Payment failed.',
                [$paymentTransaction->getEntityIdentifier()]
            );

            $this->session->getFlashBag()->add(
                'warning',
                'payever.errors.payment_failed'
            );
        }

        $event->markSuccessful();
    }

    /**
     * @param AbstractCallbackEvent $event
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

        $request = $this->request->getCurrentRequest();
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

        $this->paymentProcessor
            ->setConfig($this->payeverConfigProvider->getPaymentConfig($paymentMethod->getIdentifier()))
            ->finalize($paymentTransaction, $request);

        // Add pending payment notification
        if (QueryConstant::CALLBACK_TYPE_PENDING === $request->get(QueryConstant::PARAMETER_TYPE)) {
            $this->session->getFlashBag()->add(
                'warning',
                'payever.messages.pending_payment'
            );
        }

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

        $payload = $this->request->getCurrentRequest()->getContent();
        $this->logger->info('[Notification] Notice callback hit for payment', [$paymentTransaction->getId()]);
        $this->logger->info('[Notification] Payload: ' . $payload);

        $this->notificationRequestProcessor->processNotification(
            $payload
        );

        $event->markSuccessful();
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @param AbstractCallbackEvent $event
     * @return void
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
        } else {
            $event->markFailed();
        }
    }
}
