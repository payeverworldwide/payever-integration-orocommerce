<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Controller\Frontend;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Payever\Bundle\PaymentBundle\Attribute\Layout;
use Payever\Bundle\PaymentBundle\Constant\PaymentMethodConstant;
use Payever\Bundle\PaymentBundle\Constant\QueryConstant;
use Payever\Bundle\PaymentBundle\Service\Helper\PaymentMethodHelper;
use Payever\Bundle\PaymentBundle\Service\Payment\FinanceExpressService;
use Payever\Bundle\PaymentBundle\Service\Shipping\ShippingCalcService;
use Payever\Bundle\PaymentBundle\Service\Helper\ProductHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Psr\Log\LoggerInterface;

class FinanceExpressController extends AbstractController
{
    /**
     * @Route("/success", name="payever_payment_fe_success")
     */
    public function successAction(Request $request): RedirectResponse
    {
        $paymentId = $request->get('reference');
        $this->getLogger()->info('FinanceExpressController: handle ' . $paymentId);

        $checkout = $this->getFinanceExpressService()->handlePayment($paymentId);
        $customerUser = $this->getCurrentCustomerUser();
        if (!$customerUser || ($checkout->getCustomerUser()->getId() !== $customerUser->getId())) {
            // Customer are different. Show the generic page
            $paymentResponse = $this->getFinanceExpressService()->retrievePayment($paymentId);

            return new RedirectResponse(
                $this->getRouter()->generate(
                    'payever_payment_fe_order_success',
                    [
                        QueryConstant::PARAMETER_ORDER_REFERENCE => $paymentResponse->getReference()
                    ]
                )
            );
        }

        return new RedirectResponse(
            $this->getRouter()->generate('oro_checkout_frontend_checkout', ['id' => $checkout->getId()])
        );
    }

    /**
     * @Route("/cancel", name="payever_payment_fe_cancel")
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function cancelAction(Request $request): RedirectResponse
    {
        $this->getLogger()->info('FinanceExpressController: cancel');

        $this->getSession()->getFlashBag()->add(
            'warning',
            'payever.messages.cancelled_payment'
        );

        return new RedirectResponse(
            $this->getWidgetRedirectUrl($request)
        );
    }

    /**
     * @Route("/failure", name="payever_payment_fe_failure")
     */
    public function failureAction(Request $request): RedirectResponse
    {
        $this->getLogger()->info('FinanceExpressController: failure');

        $this->getSession()->getFlashBag()->add(
            'warning',
            'payever.messages.failed_payment'
        );
        return new RedirectResponse(
            $this->getWidgetRedirectUrl($request)
        );
    }

    /**
     * @Route("/notice", name="payever_payment_fe_notice")
     */
    public function noticeAction(Request $request): JsonResponse
    {
        $this->getLogger()->info('FinanceExpressController: notice');
        $this->getFinanceExpressService()->handleNotification($request->getContent());

        return new JsonResponse([]);
    }

    /**
     * @Route("/quote", name="payever_payment_fe_quote")
     */
    public function quoteAction(Request $request): JsonResponse
    {
        $this->getLogger()->info('FinanceExpressController: quote');
        $cart = $request->get('cart', '[]');
        $this->getLogger()->debug($cart);
        $cart = json_decode($cart, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException('Invalid cart parameter.');
        }

        $payload = $request->getContent();
        $this->getLogger()->debug($payload);
        if (empty($payload)) {
            throw new \InvalidArgumentException('Invalid payload parameter.');
        }

        $data = json_decode($payload, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException('Invalid payload parameter.');
        }

        if (isset($data['shippingMethod'])) {
            $this->getLogger()->critical('quoteAction: Selected shippingMethod: ' . $data['shippingMethod']);
            return new JsonResponse(['shippingMethods' => []]);
        }

        // Verify shippingAddress field
        if (!isset($data['shipping']) || !isset($data['shipping']['shippingAddress'])) {
            $this->getLogger()->critical('quoteAction: no shippingAddress received.');
            return new JsonResponse(['shippingMethods' => []]);
        }

        $shippingAddress = $data['shipping']['shippingAddress'];
        $result = $this->getShippingCalcServiceService()->getShippingRates(
            $cart,
            $data['shopperEmail'],
            $data['shopperPhone'],
            $shippingAddress,
            $data['currency'],
            $this->getPaymentMethodHelper()
                ->getPaymentMethod(PaymentMethodConstant::METHOD_IVY)->getIdentifier()
        );
        $this->getLogger()->debug('Shipping ETA for address: ' . json_encode($shippingAddress));
        $this->getLogger()->debug('Shipping ETA for cart: ' . json_encode($cart));
        $this->getLogger()->debug('Shipping ETA results: ' . json_encode($result));

        return new JsonResponse(['shippingMethods' => $result]);
    }

    /**
     * @Route("/order-success", name="payever_payment_fe_order_success")
     * @return array|Response
     */
    #[Layout(vars: ['reference'])]
    public function orderSuccessAction(Request $request)
    {
        return [
            'reference' => $request->get(QueryConstant::PARAMETER_ORDER_REFERENCE)
        ];
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                LoggerInterface::class,
                FinanceExpressService::class,
                ShippingCalcService::class,
                PaymentMethodHelper::class,
                ProductHelper::class
            ]
        );
    }

    private function getLogger(): LoggerInterface
    {
        return $this->container->get(LoggerInterface::class);
    }

    private function getRouter(): RouterInterface
    {
        return $this->container->get('router');
    }

    private function getSession(): SessionInterface
    {
        return $this->container->get('session');
    }

    private function getFinanceExpressService(): FinanceExpressService
    {
        return $this->container->get(FinanceExpressService::class);
    }

    private function getShippingCalcServiceService(): ShippingCalcService
    {
        return $this->container->get(ShippingCalcService::class);
    }

    private function getPaymentMethodHelper(): PaymentMethodHelper
    {
        return $this->container->get(PaymentMethodHelper::class);
    }

    private function getProductHelper(): ProductHelper
    {
        return $this->container->get(ProductHelper::class);
    }

    /**
     * Get Current Customer.
     *
     * @return CustomerUser|null
     */
    private function getCurrentCustomerUser(): ?CustomerUser
    {
        $token = $this->container->get('security.token_storage')->getToken();
        if (!$token) {
            return null;
        }

        $customerUser = $token->getUser();
        if (is_object($customerUser)) {
            return $customerUser;
        }

        return null;
    }

    private function getWidgetRedirectUrl(Request $request)
    {
        if ('product' === $request->get('type')) {
            $identifier = $request->get('identifier');
            $product = $this->getProductHelper()->getProduct($identifier);
            if ($product) {
                return $this->getProductHelper()->getProductUrl($product);
            }
        }

        if ('cart' === $request->get('type')) {
            return $this->getRouter()->generate('oro_shopping_list_frontend_view');
        }

        return $this->getRouter()->generate('oro_frontend_root');
    }
}
