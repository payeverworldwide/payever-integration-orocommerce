<?php

namespace Payever\Bundle\PaymentBundle\Service\Payment;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestination;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentMethodsConfigsRuleRepository;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Repository\CountryRepository;
use Payever\Bundle\PaymentBundle\Service\Helper\DataHelper;
use Psr\Log\LoggerInterface;

class PaymentRulesService
{
    private DataHelper $dataHelper;

    private ConfigManager $configManager;

    private ManagerRegistry $managerRegistry;

    private EntityManager $entityManager;

    private DoctrineHelper $doctrineHelper;

    protected CurrencyProviderInterface $currencyProvider;

    private LoggerInterface $logger;

    public function __construct(
        DataHelper $dataHelper,
        ConfigManager $configManager,
        ManagerRegistry $managerRegistry,
        EntityManager $entityManager,
        DoctrineHelper $doctrineHelper,
        CurrencyProviderInterface $currencyProvider,
        LoggerInterface $logger
    ) {
        $this->dataHelper = $dataHelper;
        $this->configManager = $configManager;
        $this->managerRegistry = $managerRegistry;
        $this->entityManager = $entityManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->currencyProvider = $currencyProvider;
        $this->logger = $logger;
    }

    /**
     * @param string $ruleName
     * @param string $method
     * @param array $countries
     * @param array $currencies
     * @param string $expression
     *
     * @return void
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createRule(
        string $ruleName,
        string $method,
        array $countries,
        array $currencies,
        string $expression
    ): void {
        $rule = new Rule();
        $rule->setName($ruleName)
            ->setEnabled(true)
            ->setStopProcessing(false)
            ->setSortOrder(1)
            ->setExpression($expression);

        $this->entityManager->persist($rule);
        $this->entityManager->flush($rule);

        $methodConfig = new PaymentMethodConfig();
        $methodConfig->setType($method); // oro_integration_channel: type_id

        foreach ($currencies as $currency) {
            if (!in_array($currency, $this->getAvailableCurrencies(), true)) {
                continue;
            }

            $ruleCfg = new PaymentMethodsConfigsRule();
            $ruleCfg->setOrganization($this->dataHelper->getWebsite()->getOrganization())
                ->addWebsite($this->dataHelper->getWebsite())
                ->setCurrency($currency)
                ->setRule($rule)
                ->addMethodConfig($methodConfig);

            foreach ($countries as $countryCode) {
                $country = $this->getCountry($countryCode);
                if (!$country) {
                    $this->logger->warning('Missing country: ' . $countryCode);
                    continue;
                }

                $destination = new PaymentMethodsConfigsRuleDestination();
                $destination->setCountry($country);

                $ruleCfg->addDestination($destination);
            }

            $this->entityManager->persist($ruleCfg);
            $this->entityManager->flush($ruleCfg);

            $this->logger->info(
                'Created payment rule: ' . $ruleName . ': ' . $ruleCfg->getId() . '(' . $method . ')'
            );

            $methodConfig->setMethodsConfigsRule($ruleCfg);
            $this->entityManager->persist($methodConfig);
            $this->entityManager->flush($methodConfig);
        }
    }

    /**
     * @param string $name
     *
     * @return Rule[]
     */
    public function getRules(string $name): array
    {
        return $this->getRuleRepository()->findBy(['name' => $name]);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    private function getPaymentMethodsConfigsRule($id): PaymentMethodsConfigsRule
    {
        return $this->getPaymentMethodsConfigsRuleRepository()->findOneBy(['id' => $id]);
    }

    private function getRuleRepository(): EntityRepository
    {
        return $this->managerRegistry
            ->getManagerForClass(Rule::class)
            ->getRepository(Rule::class);
    }

    private function getPaymentMethodsConfigsRuleRepository(): PaymentMethodsConfigsRuleRepository
    {
        return $this->managerRegistry->getRepository(PaymentMethodsConfigsRule::class);
    }

    private function getCountryRepository(): CountryRepository
    {
        return $this->doctrineHelper->getEntityRepository(Country::class);
    }

    private function getCountry($iso2Code): ?Country
    {
        return $this->getCountryRepository()->findOneBy(['iso2Code' => $iso2Code]);
    }

    private function getAvailableCurrencies(): array
    {
        return $this->currencyProvider->getCurrencyList();
    }
}
