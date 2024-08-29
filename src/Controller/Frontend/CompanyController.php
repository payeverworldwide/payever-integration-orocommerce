<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Controller\Frontend;

use Psr\Log\LoggerInterface;
use Payever\Bundle\PaymentBundle\Service\Company\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class CompanyController extends AbstractController
{
    /**
     * @Route("/search", name="payever_payment_company_search")
     */
    public function searchAction(
        Request $request
    ): JsonResponse {
        /** @var SearchService $searchService */
        $searchService = $this->container->get(SearchService::class);
        $country = (string) $request->get('country');
        $term = (string) $request->get('term');

        try {
            $searchResult = $searchService->search($country, $term);

            $result = [];
            foreach ($searchResult as $company) {
                $result[] = $company->toArray();
            }

            return new JsonResponse(['results' => $result]);
        } catch (\Exception $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 400);
        }
    }

    /**
     * @Route("/save", name="payever_payment_company_save")
     */
    public function saveAction(
        Request $request,
        Session $session
    ): JsonResponse {
        $externalId = (string) $request->get('externalId');
        $vatId = (string) $request->get('vatId');

        $session->set('external_id', $externalId);
        $session->set('vat_id', $vatId);

        $this->getLogger()->debug('CompanySearch: save external ID: ', [$externalId, $vatId]);

        return new JsonResponse([]);
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                LoggerInterface::class,
                SearchService::class,
            ]
        );
    }

    private function getLogger(): LoggerInterface
    {
        return $this->container->get(LoggerInterface::class);
    }
}
