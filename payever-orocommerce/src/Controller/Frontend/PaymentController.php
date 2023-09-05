<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Controller\Frontend;

use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Payever\Bundle\PaymentBundle\Entity\PayeverSettings;

class PaymentController extends AbstractController
{
    /**
     * @Route("/payment", name="payever_payment_payment")
     * @Template("@PayeverPayment/PaymentPage/view.html.twig")
     */
    public function paymentAction(Request $request)
    {
        return [
            'entity' => new PayeverSettings(),
            'iframeUrl' => $request->query->get('returnUrl')
        ];
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                LoggerInterface::class
            ]
        );
    }
}
