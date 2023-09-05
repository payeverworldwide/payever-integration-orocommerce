<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Payever\Bundle\PaymentBundle\Constant\SalutationConstant;
use Payever\Bundle\PaymentBundle\Constant\QueryConstant;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfigInterface;
use Payever\Bundle\PaymentBundle\Service\Api\ServiceProvider;
use Payever\Bundle\PaymentBundle\Service\Helper\DataHelper;
use Payever\Bundle\PaymentBundle\Service\Helper\UrlHelper;
use Payever\Bundle\PaymentBundle\Service\Helper\OrderItemHelper;
use Payever\Sdk\Core\Enum\ChannelSet;
use Payever\Sdk\Core\Enum\ChannelTypeSet;
use Payever\Sdk\Core\Http\RequestEntity;
use Payever\Sdk\Payments\Http\MessageEntity\ChannelEntity;
use Payever\Sdk\Payments\Http\MessageEntity\CustomerAddressEntity as AddressEntity;
use Payever\Sdk\Payments\Http\MessageEntity\PaymentDataEntity;
use Payever\Sdk\Payments\Http\MessageEntity\RetrievePaymentResultEntity;
use Payever\Sdk\Payments\Http\RequestEntity\CreatePaymentV2Request;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Payever\Sdk\Payments\Http\ResponseEntity\RetrievePaymentResponse;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PaymentProcessorService
{
    private const MAJORITY_YEARS = 18;

    private ServiceProvider $serviceProvider;

    private ConfigManager $configManager;

    private DataHelper $dataHelper;

    private UrlHelper $urlHelper;

    private OrderItemHelper $orderItemHelper;

    /**
     * @var DoctrineHelper
     */
    private DoctrineHelper $doctrineHelper;

    /**
     * @var LocalizationHelper
     */
    private LocalizationHelper $localizationHelper;

    private TransactionStatusService $transactionStatusService;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var PayeverConfigInterface
     */
    private PayeverConfigInterface $config;

    public function __construct(
        ServiceProvider $serviceProvider,
        ConfigManager $configManager,
        DataHelper $dataHelper,
        UrlHelper $urlHelper,
        OrderItemHelper $orderItemHelper,
        DoctrineHelper $doctrineHelper,
        LocalizationHelper $localizationHelper,
        TransactionStatusService $transactionStatusService,
        LoggerInterface $logger
    ) {
        $this->serviceProvider = $serviceProvider;
        $this->configManager = $configManager;
        $this->dataHelper = $dataHelper;
        $this->urlHelper = $urlHelper;
        $this->orderItemHelper = $orderItemHelper;
        $this->doctrineHelper = $doctrineHelper;
        $this->localizationHelper = $localizationHelper;
        $this->transactionStatusService = $transactionStatusService;
        $this->logger = $logger;
    }

    /**
     * Set Payment Config.
     *
     * @param PayeverConfigInterface $config
     *
     * @return $this
     */
    public function setConfig(PayeverConfigInterface $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     *
     * @return string
     * @throws \Exception
     */
    public function getRedirectUrl(PaymentTransaction $paymentTransaction): string
    {
        $paymentRequestEntity = $this->getCreatePaymentRequestEntity($paymentTransaction);

        $response = $this->serviceProvider
            ->getPaymentsApiClient()
            ->createPaymentV2Request($paymentRequestEntity);

        $responseEntity = $response->getResponseEntity();
        $redirectUrl = $responseEntity->getRedirectUrl();

        if (!$redirectUrl) {
            $reason = $responseEntity->getErrorDescription() ?? 'redirect_url is empty';
            throw new \UnexpectedValueException(sprintf('Create payment API error: %s', $reason));
        }

        if ($this->config->getIsSubmitMethod()) {
            // @todo Implement IsSubmit method
            $this->logger->warning('Submit method has no been implemented yet.');
        }

        if (!$this->config->getIsRedirectMethod() && !$this->configManager->get('payever_payment.is_redirect')) {
            return $this->urlHelper->generateIframeUrl($redirectUrl);
        }

        return $redirectUrl;
    }

    public function finalize(
        PaymentTransaction $paymentTransaction,
        Request $request
    ): void {
        $paymentId = $request->get(QueryConstant::PARAMETER_PAYMENT_ID);

        if (!$paymentId || QueryConstant::PAYMENT_ID_PLACEHODLER === $paymentId) {
            throw new \Exception('Payment ID is invalid.');
        }

        $order = $this->getOrder($paymentTransaction);
        if (!$order) {
            throw new \Exception('Order is not found.');
        }

        $response = $this->serviceProvider
            ->getPaymentsApiClient()
            ->retrievePaymentRequest($paymentId);

        /** @var RetrievePaymentResponse $responseEntity */
        $responseEntity = $response->getResponseEntity();

        /** @var RetrievePaymentResultEntity $payeverPayment */
        $payeverPayment = $responseEntity->getResult();

        $this->transactionStatusService->persistTransactionStatus($payeverPayment);
        $this->logger->debug('Payment has been finalized');
    }

    public function retrievePayment(PaymentTransaction $paymentTransaction): RetrievePaymentResultEntity
    {
        $paymentId = $paymentTransaction->getReference();
        if (empty($paymentId)) {
            throw new \InvalidArgumentException('Payment ID is missing.');
        }

        $response = $this->serviceProvider
            ->getPaymentsApiClient()
            ->retrievePaymentRequest($paymentId);

        /** @var RetrievePaymentResponse $responseEntity */
        $responseEntity = $response->getResponseEntity();

        /** @var RetrievePaymentResultEntity $payeverPayment */
        return $responseEntity->getResult();
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     *
     * @return RequestEntity
     */
    private function getCreatePaymentRequestEntity(PaymentTransaction $paymentTransaction): RequestEntity
    {
        return $this->populatePaymentRequestEntity(
            $paymentTransaction,
            new CreatePaymentV2Request()
        );
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @param RequestEntity $requestEntity
     *
     * @return RequestEntity
     * @throws \Exception
     */
    private function populatePaymentRequestEntity(
        PaymentTransaction $paymentTransaction,
        RequestEntity $requestEntity
    ): RequestEntity {
        $order = $this->getOrder($paymentTransaction);
        if (!$order) {
            throw new \Exception('Order is not found.');
        }

        $currentLocalization = $this->localizationHelper->getCurrentLocalization();
        $billingAddress = $order->getBillingAddress();

        $channelEntity = new ChannelEntity();
        $channelEntity
            ->setName(ChannelSet::CHANNEL_SHOPWARE)
            ->setSource($this->dataHelper->getCmsVersion())
            ->setType(ChannelTypeSet::ECOMMERCE);

        $requestEntity->setChannel($channelEntity)
            ->setPluginVersion($this->dataHelper->getPluginVersion())
            ->setPaymentMethod($this->config->getPaymentMethod())
            ->setVariantId($this->config->getVariantId())
            ->setBillingAddress($this->populateAddressEntity($billingAddress))
            ->setAmount(round((float) $paymentTransaction->getAmount(), 2))
            ->setFee(round((float) $order->getShippingCost()->getValue(), 2))
            ->setOrderId($paymentTransaction->getEntityIdentifier())
            ->setCurrency($paymentTransaction->getCurrency())
            ->setEmail($order->getEmail())
            ->setPhone($billingAddress->getPhone())
            ->setCart($this->orderItemHelper->buildCartItems($order));

        // Set Shipping address
        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
            $requestEntity->setShippingAddress($this->populateAddressEntity($shippingAddress));
        }

        // Set locale
        if ($currentLocalization) {
            $requestEntity->setLocale($currentLocalization->getLanguageCode());
        }

        // Set Birthdate
        $customerUser = $order->getCustomerUser();
        if ($customerUser) {
            $birthday = $customerUser->getBirthday();
            if (null !== $birthday && $birthday->diff(new \DateTime())->y >= self::MAJORITY_YEARS) {
                $requestEntity->setBirthdate($birthday->format('Y-m-d'));
            }
        }

        // Add Company name to payment data
        $paymentData = new PaymentDataEntity();
        $company = $billingAddress->getOrganization();
        if (!empty($company)) {
            $paymentData->setOrganizationName($company);
        }

        $isRedirectMethod = $this->config->getIsRedirectMethod() &&
            $this->configManager->get('payever_payment.is_redirect');
        $paymentData->setForceRedirect((bool) $isRedirectMethod);

        $requestEntity->setPaymentData($paymentData);

        // Set urls
        $requestEntity
            ->setSuccessUrl($this->urlHelper->getSuccessUrl($paymentTransaction))
            ->setFailureUrl($this->urlHelper->getFailureUrl($paymentTransaction))
            ->setCancelUrl($this->urlHelper->getCancelUrl($paymentTransaction))
            ->setNoticeUrl($this->urlHelper->getNoticeUrl($paymentTransaction))
            ->setPendingUrl($this->urlHelper->getPendingUrl($paymentTransaction));

        return $requestEntity;
    }

    /**
     * @param OrderAddress $address
     *
     * @return AddressEntity
     */
    private function populateAddressEntity(OrderAddress $address): AddressEntity
    {
        $addressEntity = new AddressEntity();

        $addressEntity
            ->setFirstName($address->getFirstName())
            ->setLastName($address->getLastName())
            ->setCity($address->getCity())
            ->setRegion($address->getRegionName())
            ->setZip($address->getPostalCode())
            ->setStreet($address->getStreet())
            ->setAddressLine2($address->getStreet2())
            ->setCountry($address->getCountryIso2());

        $salutation = SalutationConstant::getValidSalutation($address->getNameSuffix());
        if ($salutation) {
            $addressEntity->setSalutation($salutation);
        }

        return $addressEntity;
    }

    /**
     * Get Order entity.
     *
     * @param PaymentTransaction $paymentTransaction
     *
     * @return Order|null
     */
    private function getOrder(PaymentTransaction $paymentTransaction): ?Order
    {
        /** @var Order $entity */
        return $this->doctrineHelper->getEntityReference(
            $paymentTransaction->getEntityClass(),
            $paymentTransaction->getEntityIdentifier()
        );
    }
}
