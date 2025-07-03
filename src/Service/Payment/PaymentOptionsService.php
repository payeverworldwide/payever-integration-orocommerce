<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\IntegrationBundle\Manager\DeleteManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Payever\Bundle\PaymentBundle\Integration\PayeverChannelType;
use Payever\Bundle\PaymentBundle\Entity\PayeverSettings as Transport;
use Payever\Bundle\PaymentBundle\Service\Api\ServiceProvider;
use Payever\Bundle\PaymentBundle\Service\Helper\DataHelper;
use Payever\Sdk\Payments\Converter\PaymentOptionConverter;
use Payever\Sdk\Payments\Http\MessageEntity\ConvertedPaymentOptionEntity;
use Payever\Sdk\Payments\Http\MessageEntity\ListPaymentOptionsVariantsResultEntity;
use Psr\Log\LoggerInterface;

class PaymentOptionsService
{
    private const PAYMENT_METHOD_PREFIX = 'payever_';

    private ServiceProvider $serviceProvider;

    private PaymentRulesService $paymentRulesService;

    private DataHelper $dataHelper;

    private ConfigManager $configManager;
    private ConfigManager $configGlobal;

    private ManagerRegistry $managerRegistry;

    private EntityManager $entityManager;
    private DoctrineHelper $doctrineHelper;

    private DeleteManager $deleteManager;

    private LoggerInterface $logger;

    /**
     * @param ServiceProvider $serviceProvider
     * @param PaymentRulesService $paymentRulesService
     * @param DataHelper $dataHelper
     * @param ConfigManager $configManager
     * @param ConfigManager $configGlobal
     * @param ManagerRegistry $managerRegistry
     * @param EntityManager $entityManager
     * @param DoctrineHelper $doctrineHelper
     * @param DeleteManager $deleteManager
     * @param LoggerInterface $logger
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ServiceProvider $serviceProvider,
        PaymentRulesService $paymentRulesService,
        DataHelper $dataHelper,
        ConfigManager $configManager,
        ConfigManager $configGlobal,
        ManagerRegistry $managerRegistry,
        EntityManager $entityManager,
        DoctrineHelper $doctrineHelper,
        DeleteManager $deleteManager,
        LoggerInterface $logger
    ) {
        $this->serviceProvider = $serviceProvider;
        $this->paymentRulesService = $paymentRulesService;
        $this->dataHelper = $dataHelper;
        $this->configManager = $configManager;
        $this->configGlobal = $configGlobal;
        $this->managerRegistry = $managerRegistry;
        $this->entityManager = $entityManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->deleteManager = $deleteManager;
        $this->logger = $logger;
    }

    /**
     * @return void
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function synchronizePaymentOptions()
    {
        // Remove exists channels / transports
        $integrations = $this->getChannelRepository()->findBy(['type' => PayeverChannelType::TYPE]);
        foreach ($integrations as $integration) {
            $this->deleteManager->delete($integration);
        }

        $this->logger->info('Channels / transports removed');

        $businessUuid = $this->configManager->get('payever_payment.business_uuid');
        if (empty($businessUuid)) {
            throw new \UnexpectedValueException(
                'Please enter Business UUID.'
            );
        }

        $paymentMethods = $this->getPaymentOptions($businessUuid);
        $b2bCountries = [];
        $ruleNames = [];
        foreach ($paymentMethods as $paymentMethod) {
            $currencies = (array) $paymentMethod->getOptions()->getCurrencies();
            $countries = (array) $paymentMethod->getOptions()->getCountries();

            $isB2BMethod = (bool) $paymentMethod->isB2BMethod();
            if ($isB2BMethod) {
                $this->addB2BCountries($paymentMethod, $b2bCountries);
            }

            $transport = new Transport();
            $transport
                ->setPaymentMethod($paymentMethod->getPaymentMethod())
                ->setVariantId($paymentMethod->getVariantId())
                ->setDescriptionOffer(strip_tags((string) $paymentMethod->getDescriptionOffer()))
                ->setDescriptionFee(strip_tags((string) $paymentMethod->getDescriptionFee()))
                ->setIsRedirectMethod((bool)$paymentMethod->isRedirectMethod())
                ->setIsSubmitMethod((bool)$paymentMethod->getIsSubmitMethod())
                ->setIsB2BMethod($isB2BMethod)
                ->setInstructionText(strip_tags((string) $paymentMethod->getInstructionText()))
                ->setThumbnail($paymentMethod->getThumbnail1())
                ->setCurrencies($currencies)
                ->setCountries($countries)
                ->setIsShippingAddressAllowed($paymentMethod->getShippingAddressAllowed())
                ->setIsShippingAddressEquality($paymentMethod->getShippingAddressEquality())
                ->setMax($paymentMethod->getMax())
                ->setMin($paymentMethod->getMin())
                ->setIsAcceptFee($paymentMethod->getAcceptFee())
                ->setVariableFee($paymentMethod->getVariableFee())
                ->setFixedFee($paymentMethod->getFixedFee());

            $integration = new Integration();
            $integration
                ->setOrganization($this->dataHelper->getWebsite()->getOrganization())
                ->setTransport($transport)
                ->setName($paymentMethod->getName())
                ->setType(PayeverChannelType::TYPE)
                ->setConnectors([])
                ->setEnabled(true)
                ->setDefaultUserOwner($this->getDefaultUserOwner());

            $this->entityManager->persist($integration);
            $this->entityManager->flush($integration);

            $paymentName =  $paymentMethod->getName();
            $variantName = $paymentMethod->getVariantName();
            if ($variantName) {
                $paymentName = sprintf("%s-%s", $paymentName, $variantName);
            }
            // @codeCoverageIgnoreStart
            // Name localization
            $this->addLocalization(
                $paymentName,
                $integration->getTransport()->getId()
            );

            $this->logger->debug(
                'Synchronization: Added: ' . $paymentName . ': ' . (string) $integration->getId(),
                [
                    $currencies,
                    $countries
                ]
            );

            // Create Payment Method Rule
            $ruleName = $paymentMethod->getPaymentMethod() . ' rule';
            if (in_array($ruleName, $ruleNames)) {
                $ruleName = $paymentMethod->getPaymentMethod() . '-' . $paymentMethod->getVariantId() . ' rule';
            }
            $ruleNames[] = $ruleName;
            $rules = $this->paymentRulesService->getRules($ruleName);
            foreach ($rules as $rule) {
                $this->logger->debug('Rule removed: ' . $ruleName);
                $this->entityManager->remove($rule);
                $this->entityManager->flush($rule);
            }

            $this->paymentRulesService->createRule(
                $ruleName,
                PayeverChannelType::TYPE . '_' . (string) $integration->getId(),
                $countries,
                $currencies,
                $this->getPaymentMethodExpression($paymentMethod->getShippingAddressEquality())
            );
            // @codeCoverageIgnoreEnd
        }

        // Remove workflows
        $this->removeWorkflows();

        // Save B2B countries
        $this->configGlobal->set('payever_payment.b2b_countries', array_unique(array_values($b2bCountries)));
        $this->configGlobal->flush();

        $this->logger->info('Synchronization has been finished');
    }

    /**
     * Get payment options for synchronization
     *
     * @param $businessUuid
     * @return ConvertedPaymentOptionEntity[]
     * @throws \Exception
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function getPaymentOptions($businessUuid)
    {
        /** @var ListPaymentOptionsVariantsResultEntity[] $variants */
        $variants = $this->serviceProvider
            ->getPaymentsApiClient()
            ->listPaymentOptionsWithVariantsRequest([], $businessUuid)
            ->getResponseEntity()
            ->getResult();

        return PaymentOptionConverter::convertPaymentOptionVariants($variants);
    }

    /**
     * Add localization.
     *
     * @param string $string
     * @param int $transportId
     * @return void
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @codeCoverageIgnore
     */
    private function addLocalization(string $string, int $transportId)
    {
        $localization = new LocalizedFallbackValue();
        $localization->setString($string);
        $this->entityManager->persist($localization);
        $this->entityManager->flush($localization);

        // Add short localization
        $statement = $this->getConnection()->prepare(
            'INSERT INTO payever_short_label (transport_id, localized_value_id) VALUES (:transport_id, :localized_value_id);' //phpcs:ignore
        );
        $statement->bindValue(
            'transport_id',
            $transportId
        );
        $statement->bindValue(
            'localized_value_id',
            $localization->getId()
        );
        $statement->execute();

        // Add localization
        $statement = $this->getConnection()->prepare(
            'INSERT INTO payever_trans_label (transport_id, localized_value_id) VALUES (:transport_id, :localized_value_id);' //phpcs:ignore
        );
        $statement->bindValue(
            'transport_id',
            $transportId
        );
        $statement->bindValue(
            'localized_value_id',
            $localization->getId()
        );
        $statement->execute();
    }

    /**
     * @return void
     * @codeCoverageIgnore
     */
    private function removeWorkflows(): void
    {
        $repository = $this->managerRegistry
            ->getManagerForClass(CheckoutWorkflowState::class)
            ->getRepository(CheckoutWorkflowState::class);

        $workFlows = $repository->findAll();
        foreach ($workFlows as $workFlow) {
            /** @var CheckoutWorkflowState $workFlow */
            $data = $workFlow->getStateData();
            if (isset($data['payment_method']) && str_contains($data['payment_method'], self::PAYMENT_METHOD_PREFIX)) {
                $this->entityManager->remove($workFlow);
                $this->entityManager->flush();
            }
        }
    }

    /**
     * @return User
     * @codeCoverageIgnore
     */
    private function getDefaultUserOwner(): User
    {
        return $this->getUserRepository()->findOneBy([], ['id' => 'ASC']);
    }

    /**
     * @return object
     * @codeCoverageIgnore
     */
    protected function getConnection()
    {
        return $this->managerRegistry->getConnection();
    }

    /**
     * @return UserRepository
     */
    private function getUserRepository(): UserRepository
    {
        return $this->managerRegistry
            ->getManagerForClass(User::class)
            ->getRepository(User::class);
    }

    /**
     * @return ChannelRepository
     */
    private function getChannelRepository(): ChannelRepository
    {
        return $this->doctrineHelper->getEntityRepository(Channel::class);
    }

    /**
     * @param $shippingAddressEquality
     * @return string
     * @codeCoverageIgnore
     */
    private function getPaymentMethodExpression($shippingAddressEquality): string
    {
        return $shippingAddressEquality ? 'shippingAddress.street = billingAddress.street and
        shippingAddress.street2 = billingAddress.street2 and
        shippingAddress.city = billingAddress.city and
        shippingAddress.regionName = billingAddress.regionName and
        shippingAddress.regionCode = billingAddress.regionCode and
        shippingAddress.postalCode = billingAddress.postalCode and
        shippingAddress.countryName = billingAddress.countryName and
        shippingAddress.countryIso3 = billingAddress.countryIso3 and
        shippingAddress.countryIso2 = billingAddress.countryIso2
        ' : '';
    }

    /**
     * @param ConvertedPaymentOptionEntity $paymentMethod
     * @param array $b2bCountries
     * @return void
     * @codeCoverageIgnore
     */
    private function addB2BCountries(ConvertedPaymentOptionEntity $paymentMethod, array &$b2bCountries): void
    {
        $variantOptions = $paymentMethod->getVariantOptions();
        $variantB2bCountries = $variantOptions ? $variantOptions->getCountries() : [];

        // Use old method as failback
        if (empty($variantB2bCountries)) {
            $variantB2bCountries = $paymentMethod->getOptions()->getCountries();
        }

        $b2bCountries += $variantB2bCountries;

        $this->logger->debug(
            sprintf(
                'Found B2B payment method: %s %s(%s). Countries: %s',
                $paymentMethod->getPaymentMethod(),
                $paymentMethod->getVariantId(),
                $paymentMethod->getVariantName(),
                json_encode($variantB2bCountries)
            )
        );
    }
}
