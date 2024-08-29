<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Company;

use Payever\Bundle\PaymentBundle\Service\Api\ServiceProvider;
use Payever\Sdk\Payments\PaymentsApiClient;
use Payever\Sdk\Payments\Http\RequestEntity\CompanySearchCreditRequest;
use Payever\Sdk\Payments\Http\RequestEntity\CompanySearchCredit\CompanyEntity;
use Payever\Sdk\Payments\Http\ResponseEntity\CompanySearchCreditResponse;

class CompanyCreditService
{
    /**
     * @var ServiceProvider
     */
    private ServiceProvider $serviceProvider;

    /**
     * @var CompanySearchCreditResponse[]
     */
    private array $companyCredit = [];

    public function __construct(ServiceProvider $serviceProvider)
    {
        $this->serviceProvider = $serviceProvider;
    }

    /**
     * @param string $externalId
     * @return CompanySearchCreditResponse|null
     */
    public function getCompanyCredit(string $externalId): ?CompanySearchCreditResponse
    {
        if (isset($this->companyCredit[$externalId])) {
            return $this->companyCredit[$externalId];
        }

        try {
            $this->companyCredit[$externalId] = $this->getCredit(
                $externalId
            );

            return $this->companyCredit[$externalId];
        } catch (\Exception $exception) {
            $this->companyCredit[$externalId] = null;
        }

        return null;
    }

    /**
     * Get Company Credit Data.
     *
     * @param string $externalId
     * @return CompanySearchCreditResponse
     * @throws \Exception
     */
    public function getCredit(string $externalId): CompanySearchCreditResponse
    {
        /** @var PaymentsApiClient $paymentsApiClient */
        $paymentsApiClient = $this->serviceProvider->getPaymentsApiClient();

        $company = new CompanyEntity();
        $company->setExternalId($externalId);

        $companySearchCredit = new CompanySearchCreditRequest();
        $companySearchCredit->setCompany($company);

        return $paymentsApiClient->companyCredit($companySearchCredit)->getResponseEntity();
    }
}
