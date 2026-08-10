<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Controller\Frontend;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Payever\Bundle\PaymentBundle\Attribute\Layout;
use Payever\Bundle\PaymentBundle\Constant\QueryConstant;
use Payever\Bundle\PaymentBundle\Service\Helper\PaymentHelper;
use Payever\Bundle\PaymentBundle\Service\Helper\UrlHelper;
use Payever\Bundle\PaymentBundle\Service\Management\CheckoutManager;
use Payever\Sdk\Core\Lock\FileLock;
use Payever\Sdk\Payments\Enum\Status;
use Payever\Sdk\Payments\Notification\NotificationRequestProcessor;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Acl(
    id: 'payever_callback_checkout',
    type: 'entity',
    class: Checkout::class,
    permission: 'EDIT',
    groupName: 'commerce'
)]
class CallbackController extends AbstractController
{
    const SANTANDER_PREFIX = 'santander_';

    /**
     * @Route("/success/{checkoutId}", name="payever_callback_success", requirements={"checkoutId"="\d+"}, methods={"GET"})
     * @ParamConverter("checkout", options={"mapping": {"checkoutId": "id"}})
     *
     * @param Checkout $checkout
     * @param Request $request
     * @param CheckoutManager $checkoutManager
     * @param PaymentHelper $paymentHelper
     * @param FileLock $locker
     *
     * @return RedirectResponse
     */
    public function successAction(
        Checkout $checkout,
        Request $request,
        CheckoutManager $checkoutManager,
        PaymentHelper $paymentHelper,
        FileLock $locker
    ): RedirectResponse {
        $paymentId = $request->get(QueryConstant::PARAMETER_PAYMENT_ID);

        try {
            if (!$paymentId || QueryConstant::PAYMENT_ID_PLACEHODLER === $paymentId) {
                throw new \LogicException('Invalid payload parameter.');
            }

            if (!$paymentHelper->isPaymentSuccess($paymentId)) {
                throw new \LogicException('The payment was not successful.');
            }

            // Locker
            $this->getLogger()->debug(sprintf('[Callback] Attempting to lock %s', $paymentId));
            $locker->acquireLock($paymentId, NotificationRequestProcessor::NOTIFICATION_LOCK_SECONDS);
            $this->getLogger()->debug(sprintf('[Callback] Locked  %s', $paymentId));

            $payeverCheckout = $checkoutManager->completeCheckoutPayment($checkout, $paymentId);

            // Locker
            $locker->releaseLock($paymentId);
            $this->getLogger()->debug(sprintf('[Callback] Unlocked  %s', $paymentId));

            return $this->redirect($payeverCheckout->getReturnUrl());
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            $this->getLogger()->critical('[Callback] Success callback error: ' . $e->getMessage(), [$checkout->getId()]);

            // Locker
            $locker->releaseLock($paymentId);
            $this->getLogger()->debug(sprintf('[Callback] Unlocked  %s', $paymentId));

            return $this->redirectToRoute('oro_checkout_frontend_checkout', [
                'id' => $checkout->getId(),
                'transition' => 'payment_error',
            ]);
        }
    }

    /**
     * @Route("/pending/{checkoutId}", name="payever_callback_pending", requirements={"checkoutId"="\d+"}, methods={"GET"})
     * @ParamConverter("checkout", options={"mapping": {"checkoutId": "id"}})
     *
     * @param Checkout $checkout
     * @param Request $request
     * @param PaymentHelper $paymentHelper
     * @param UrlHelper $urlHelper
     *
     * @return RedirectResponse|array
     */
    #[Layout(vars: ['api_order_update_status', 'is_loan_transaction'])]
    public function pendingAction(
        Checkout $checkout,
        Request $request,
        PaymentHelper $paymentHelper,
        UrlHelper $urlHelper
    ): RedirectResponse|array {
        $paymentId = $request->get(QueryConstant::PARAMETER_PAYMENT_ID);

        try {
            if (!$paymentId || QueryConstant::PAYMENT_ID_PLACEHODLER === $paymentId) {
                throw new \LogicException('Invalid payload parameter.');
            }

            $payment = $paymentHelper->retrievePayment($paymentId);
            $isSantander = str_contains($payment->getPaymentType(), self::SANTANDER_PREFIX);

            return [
                'is_loan_transaction' => $isSantander,
                'api_order_update_status' => $urlHelper->generateStatusUpdateUrl($checkout->getId(), $paymentId)
            ];
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            $this->getLogger()->error('Pending callback error: ' . $e->getMessage(), [$checkout->getId()]);

            return $this->redirectToRoute('oro_checkout_frontend_checkout', [
                'id' => $checkout->getId(),
                'transition' => 'payment_error',
            ]);
        }
    }

    /**
     * @Route("/cancel/{checkoutId}", name="payever_callback_cancel", requirements={"checkoutId"="\d+"}, methods={"GET"})
     * @ParamConverter("checkout", options={"mapping": {"checkoutId": "id"}})
     *
     * @param Checkout $checkout
     *
     * @return RedirectResponse
     */
    public function cancelAction(Checkout $checkout): RedirectResponse
    {
        $this->addFlash('warning', 'payever.errors.payment_cancelled_v2');
        $this->getLogger()->info('Payment has been cancelled by customer.', [$checkout->getId()]);

        return $this->redirectToRoute('oro_checkout_frontend_checkout', [
            'id' => $checkout->getId(),
            'transition' => 'payment_error',
        ]);
    }

    /**
     * @Route("/failure/{checkoutId}", name="payever_callback_failure", requirements={"checkoutId"="\d+"}, methods={"GET"})
     * @ParamConverter("checkout", options={"mapping": {"checkoutId": "id"}})
     *
     * @param Checkout $checkout
     * @param Request $request
     * @param PaymentHelper $paymentHelper
     *
     * @return RedirectResponse
     *
     * @throws \Exception
     */
    public function failureAction(
        Checkout $checkout,
        Request $request,
        PaymentHelper $paymentHelper
    ): RedirectResponse {
        $logText = 'Payment failed.';
        $flashText = 'payever.errors.payment_failed_v2';

        try {
            $paymentId = $request->get(QueryConstant::PARAMETER_PAYMENT_ID);
            if ($paymentId && QueryConstant::PAYMENT_ID_PLACEHODLER !== $paymentId) {
                $paymentStatus = $paymentHelper->retrievePaymentStatus($paymentId);
                if ($paymentStatus == Status::STATUS_DECLINED) {
                    $logText = 'Payment declined.';
                    $flashText = 'payever.errors.payment_declined';
                }
            }
        } catch (\Exception $e) {
            $this->getLogger()->error('Failure callback error: ' . $e->getMessage(), [$checkout->getId()]);
        }

        $this->getLogger()->info($logText, [$checkout->getId()]);
        $this->addFlash('warning', $flashText);

        return $this->redirectToRoute('oro_checkout_frontend_checkout', [
            'id' => $checkout->getId(),
            'transition' => 'payment_error',
        ]);
    }

    /**
     * @Route("/status_update/{checkoutId}", name="payever_callback_status_update", requirements={"checkoutId"="\d+"}, methods={"GET"})
     * @ParamConverter("checkout", options={"mapping": {"checkoutId": "id"}})
     *
     * @param Checkout $checkout
     * @param Request $request
     * @param PaymentHelper $paymentHelper
     * @param UrlHelper $urlHelper
     *
     * @return JsonResponse
     */
    public function statusUpdateAction(
        Checkout $checkout,
        Request $request,
        PaymentHelper $paymentHelper,
        UrlHelper $urlHelper
    ): JsonResponse {
        $paymentId = $request->get(QueryConstant::PARAMETER_PAYMENT_ID);

        try {
            if (!$paymentId) {
                throw new \InvalidArgumentException('Invalid payload parameter.');
            }

            $paymentStatus = $paymentHelper->retrievePaymentStatus($paymentId);
            $redirectUrl = $urlHelper->getCallbackUrl($paymentStatus, $checkout->getId(), $paymentId);

            return new JsonResponse(['result' => 'success', 'url' => $redirectUrl]);
        } catch (\Exception $e) {
            $this->getLogger()->error('Status check error: ' . $e->getMessage(), [$checkout->getId()]);

            return new JsonResponse(['result' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            LoggerInterface::class,
        ]);
    }

    /**
     * @return LoggerInterface
     */
    private function getLogger(): LoggerInterface
    {
        return $this->container->get(LoggerInterface::class);
    }
}
