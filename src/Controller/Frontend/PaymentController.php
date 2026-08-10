<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Controller\Frontend;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Payever\Bundle\PaymentBundle\Attribute\Layout;
use Payever\Bundle\PaymentBundle\Constant\QueryConstant;
use Payever\Bundle\PaymentBundle\Service\Management\CheckoutManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PaymentController extends AbstractController
{
    /**
     * @Route("/create/{id}", name="payever_payment_create", requirements={"id"="\d+"}, methods={"POST"})
     *
     * @param Checkout $checkout
     * @param CheckoutManager $checkoutManager
     *
     * @return Response
     *
     * @throws \Throwable
     */
    public function createAction(Checkout $checkout, CheckoutManager $checkoutManager): Response
    {
        try {
            $redirectUrl = $checkoutManager->initiateCheckoutPayment($checkout);

            return new JsonResponse(['result' => 'success', 'redirectUrl' => $redirectUrl]);
        } catch (\Exception $e) {
            return new JsonResponse(['result' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * @Route("/payment", name="payever_payment_iframe", methods={"GET"})
     *
     * @return array
     */
    #[Layout(vars: ['iframeUrl'])]
    public function iframeAction(Request $request)
    {
        return [
            'iframeUrl' => $request->query->get(QueryConstant::PARAM_PAYMENT_URL)
        ];
    }
}
