<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState;
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

    private ManagerRegistry $managerRegistry;

    private EntityManager $entityManager;

    private DeleteManager $deleteManager;

    private LoggerInterface $logger;

    public function __construct(
        ServiceProvider $serviceProvider,
        PaymentRulesService $paymentRulesService,
        DataHelper $dataHelper,
        ConfigManager $configManager,
        ManagerRegistry $managerRegistry,
        EntityManager $entityManager,
        DeleteManager $deleteManager,
        LoggerInterface $logger
    ) {
        $this->serviceProvider = $serviceProvider;
        $this->paymentRulesService = $paymentRulesService;
        $this->dataHelper = $dataHelper;
        $this->configManager = $configManager;
        $this->managerRegistry = $managerRegistry;
        $this->entityManager = $entityManager;
        $this->deleteManager = $deleteManager;
        $this->logger = $logger;
    }

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
        foreach ($paymentMethods as $paymentMethod) {
            $currencies = $paymentMethod->getOptions()->getCurrencies();
            $countries = $paymentMethod->getOptions()->getCountries();

            $transport = new Transport();
            $transport
                ->setPaymentMethod($paymentMethod->getPaymentMethod())
                ->setVariantId($paymentMethod->getVariantId())
                ->setDescriptionOffer(strip_tags((string) $paymentMethod->getDescriptionOffer()))
                ->setDescriptionFee(strip_tags((string) $paymentMethod->getDescriptionFee()))
                ->setIsRedirectMethod($paymentMethod->getIsRedirectMethod())
                ->setIsSubmitMethod($paymentMethod->getIsSubmitMethod())
                ->setInstructionText(strip_tags((string) $paymentMethod->getInstructionText()))
                ->setThumbnail($paymentMethod->getThumbnail1())
                ->setCurrencies($currencies)
                ->setCountries($countries)
                ->setIsShippingAddressAllowed($paymentMethod->getShippingAddressEquality())
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

            // Name localization
            $this->addLocalization(
                $paymentMethod->getName(),
                $integration->getTransport()->getId()
            );

            $this->logger->debug(
                'Synchronization: ' . $paymentMethod->getName() . ': ' . (string) $integration->getId()
            );

            // Create Payment Method Rule
            $ruleName = $paymentMethod->getPaymentMethod() . ' rule';
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
                $currencies
            );
        }

        // Remove workflows
        $this->removeWorkflows();

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

    private function getDefaultUserOwner(): User
    {
        return $this->getUserRepository()->findOneBy([], ['id' => 'ASC']);
    }

    /**
     * @return object
     */
    protected function getConnection()
    {
        return $this->managerRegistry->getConnection();
    }

    private function getUserRepository(): UserRepository
    {
        return $this->managerRegistry
            ->getManagerForClass(User::class)
            ->getRepository(User::class);
    }

    private function getChannelRepository(): ChannelRepository
    {
        return $this->managerRegistry->getRepository('OroIntegrationBundle:Channel');
    }
}
