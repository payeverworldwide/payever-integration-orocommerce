<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Company;

use Payever\Bundle\PaymentBundle\Service\Api\ServiceProvider;
use Payever\Bundle\PaymentBundle\Service\Helper\AddressHelper;
use Payever\Sdk\Payments\Http\MessageEntity\CompanySearchResultEntity;
use Payever\Sdk\Payments\Http\RequestEntity\CompanySearch\AddressEntity;
use Payever\Sdk\Payments\Http\RequestEntity\CompanySearch\CompanyEntity;
use Payever\Sdk\Payments\Http\RequestEntity\CompanySearchRequest;
use Payever\Sdk\Payments\Http\ResponseEntity\CompanySearchResponse;

class SearchService
{
    /**
     * @var ServiceProvider
     */
    private ServiceProvider $serviceProvider;

    /**
     * @var AddressHelper
     */
    private AddressHelper $addressHelper;

    public function __construct(
        ServiceProvider $serviceProvider,
        AddressHelper $addressHelper
    ) {
        $this->serviceProvider = $serviceProvider;
        $this->addressHelper = $addressHelper;
    }

    /**
     * Search Company.
     *
     * @param string $country
     * @param string $term
     * @return CompanySearchResultEntity[]
     */
    public function search(string $country, string $term): array
    {
        $companyEntity = new CompanyEntity();
        $companyEntity->setName($term);

        $addressEntity = new AddressEntity();
        $addressEntity->setCountry($country);

        $companySearchRequestEntity = new CompanySearchRequest();
        $companySearchRequestEntity->setCompany($companyEntity);
        $companySearchRequestEntity->setAddress($addressEntity);

        $response = $this->serviceProvider->getPaymentsApiClient()
            ->searchCompany($companySearchRequestEntity);

        /** @var CompanySearchResponse $responseEntity */
        $responseEntity = $response->getResponseEntity();

        $results = $responseEntity->getResult();
        foreach ($results as &$result) {
            /** @var CompanySearchResultEntity $result */
            $address = $result->getAddress();
            if (!$address) {
                continue;
            }

            // Resolve region
            $countryCode = (string) $address->getCountryCode();
            $stateCode = (string) $address->getStateCode();
            $region = $this->addressHelper->resolveRegion($countryCode, $stateCode);
            if ($region) {
                $address->setStateCode($region->getCombinedCode());
            }
        }

        return $results;
    }
}
