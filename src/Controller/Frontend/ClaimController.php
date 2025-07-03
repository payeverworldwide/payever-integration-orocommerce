<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Controller\Frontend;

use Payever\Bundle\PaymentBundle\Service\Helper\AuthHelper;
use Payever\Bundle\PaymentBundle\Service\Payment\InvoiceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ClaimController extends AbstractController
{
    /**
     * @Route("/invoice/{attachmentId}", name="payever_claim_invoice_get")
     */
    public function showInvoiceAction(
        Request $request,
        AuthHelper $authHelper,
        InvoiceService $invoiceService,
        int $attachmentId
    ): Response {
        $response = $this->requestMustBeAuthorized($request, $authHelper);
        if ($response) {
            return $response;
        }

        $attachment = $invoiceService->getAttachmentById($attachmentId);
        $fileContent = $invoiceService->getInvoiceByAttachmentFile($attachment);

        $response = new Response($fileContent);
        $response->headers->set('Content-Type', $attachment->getFile()->getMimeType());

        return $response;
    }

    /**
     * @Route("/invoices", name="payever_claim_inoice_list")
     */
    public function showInvoices(Request $request, InvoiceService $invoiceService, AuthHelper $authHelper): Response
    {
        $response = $this->requestMustBeAuthorized($request, $authHelper);
        if ($response) {
            return $response;
        }

        $buyerId = (string)$request->query->get('buyerId');
        if (empty($buyerId)) {
            return new JsonResponse(['error' => '`buyerId` parameter is missing'], Response::HTTP_BAD_REQUEST);
        }

        $result = [];
        $invoices = $invoiceService->getInvoicesByExternalId($buyerId);
        foreach ($invoices as $invoice) {
            $result[] = [
                'url' => $this->generateUrl(
                    'payever_claim_invoice_get',
                    [
                        'attachmentId' => $invoice->getAttachmentId(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                'paymentId' => $invoice->getPaymentId(),
            ];
        }

        return new JsonResponse($result);
    }

    /**
     * Checks if the request must be authorized and returns an error response if not.
     *
     * @param Request $request The Symfony request object.
     *
     * @return Response|null The Symfony response object if the request must be authorized, otherwise null.
     */
    private function requestMustBeAuthorized(Request $request, AuthHelper $authHelper): ?Response
    {
        $user = (string)$request->headers->get('PHP_AUTH_USER');
        $password = (string)$request->headers->get('PHP_AUTH_PW');
        if (!$authHelper->validateUserCredentials($user, $password)) {
            return $this->createAuthenticateResponse();
        }

        return null;
    }

    /**
     * Creates an authentication response with 401 Unauthorized status code and www-authenticate header.
     *
     * @return Response The authentication response.
     */
    private function createAuthenticateResponse(): Response
    {
        $response = new Response('Unauthorized', 401);
        $response->headers->set('WWW-Authenticate', 'Basic realm="Shopware admin user authentication"');

        return $response;
    }

    /**
     * @return array
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            [
                AuthHelper::class,
                InvoiceService::class,
            ],
            parent::getSubscribedServices()
        );
    }
}
