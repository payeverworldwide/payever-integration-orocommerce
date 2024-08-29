<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Request;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserLoggingInfoProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Payever\Bundle\PaymentBundle\Constant\QueryConstant;
use Payever\Bundle\PaymentBundle\Constant\SalutationConstant;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfigInterface;
use Payever\Bundle\PaymentBundle\Service\Api\ServiceProvider;
use Payever\Bundle\PaymentBundle\Service\Helper\DataHelper;
use Payever\Bundle\PaymentBundle\Service\Helper\OrderItemHelper;
use Payever\Bundle\PaymentBundle\Service\Helper\UrlHelper;
use Payever\Bundle\PaymentBundle\Service\Payment\TransactionStatusService;
use Payever\Sdk\Core\Enum\ChannelSet;
use Payever\Sdk\Core\Enum\ChannelTypeSet;
use Payever\Sdk\Core\Http\RequestEntity;
use Payever\Sdk\Payments\Enum\Status;
use Payever\Sdk\Payments\Http\MessageEntity\AttributesEntity;
use Payever\Sdk\Payments\Http\MessageEntity\CartItemV3Entity;
use Payever\Sdk\Payments\Http\MessageEntity\ChannelEntity;
use Payever\Sdk\Payments\Http\MessageEntity\CompanyEntity;
use Payever\Sdk\Payments\Http\MessageEntity\CustomerAddressEntity as AddressEntity;
use Payever\Sdk\Payments\Http\MessageEntity\CustomerEntity;
use Payever\Sdk\Payments\Http\MessageEntity\DimensionsEntity;
use Payever\Sdk\Payments\Http\MessageEntity\PaymentDataEntity;
use Payever\Sdk\Payments\Http\MessageEntity\PurchaseEntity;
use Payever\Sdk\Payments\Http\MessageEntity\ShippingOptionEntity;
use Payever\Sdk\Payments\Http\MessageEntity\SubmitPaymentResultEntity;
use Payever\Sdk\Payments\Http\MessageEntity\UrlsEntity;
use Payever\Sdk\Payments\Http\RequestEntity\CreatePaymentV3Request;
use Payever\Sdk\Payments\Http\RequestEntity\SubmitPaymentRequestV3;
use Payever\Sdk\Payments\Http\ResponseEntity\RetrievePaymentResponse;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class PopulatePaymentRequestV3
{
    private const MAJORITY_YEARS = 18;
    private const CUSTOMER_TYPE_PERSON = 'person';
    private const CUSTOMER_TYPE_ORGANIZATION = 'organization';

    private ServiceProvider $serviceProvider;

    private DataHelper $dataHelper;

    private UrlHelper $urlHelper;

    private OrderItemHelper $orderItemHelper;

    /**
     * @var DoctrineHelper
     */
    private DoctrineHelper $doctrineHelper;

    private TransactionStatusService $transactionStatusService;

    private PaymentTransactionProvider $paymentTransactionProvider;

    private CustomerUserLoggingInfoProvider $customerUserLoggingInfoProvider;

    private Session $session;

    /**
     * @var PayeverConfigInterface
     */
    private PayeverConfigInterface $config;

    public function __construct(
        ServiceProvider $serviceProvider,
        DataHelper $dataHelper,
        UrlHelper $urlHelper,
        OrderItemHelper $orderItemHelper,
        DoctrineHelper $doctrineHelper,
        TransactionStatusService $transactionStatusService,
        PaymentTransactionProvider $paymentTransactionProvider,
        CustomerUserLoggingInfoProvider $customerUserLoggingInfoProvider,
        Session $session
    ) {
        $this->serviceProvider = $serviceProvider;
        $this->dataHelper = $dataHelper;
        $this->urlHelper = $urlHelper;
        $this->orderItemHelper = $orderItemHelper;
        $this->doctrineHelper = $doctrineHelper;
        $this->transactionStatusService = $transactionStatusService;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
        $this->customerUserLoggingInfoProvider = $customerUserLoggingInfoProvider;
        $this->session = $session;
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
            ->createPaymentV3Request($paymentRequestEntity);

        $responseEntity = $response->getResponseEntity();
        $redirectUrl = $responseEntity->getRedirectUrl();

        if (!$redirectUrl) {
            $reason = $responseEntity->getErrorDescription() ?? 'redirect_url is empty';
            throw new \UnexpectedValueException(sprintf('Create payment API error: %s', $reason));
        }

        return $redirectUrl;
    }

    /**
     * Get Submit Redirect Url.
     *
     * @param PaymentTransaction $paymentTransaction
     * @return string
     * @throws \Throwable
     */
    public function getSubmitRedirectUrl(PaymentTransaction $paymentTransaction): string
    {
        $paymentRequestEntity = $this->getSubmitPaymentRequestEntity($paymentTransaction);

        $response = $this->serviceProvider
            ->getPaymentsApiClient()
            ->submitPaymentRequestV3($paymentRequestEntity);

        /** @var RetrievePaymentResponse $responseEntity */
        $responseEntity = $response->getResponseEntity();

        /** @var SubmitPaymentResultEntity $result */
        $result = $responseEntity->getResult();
        $paymentId = $result->getId();

        $paymentTransaction
            ->setReference($paymentId)
            ->setResponse($result->toArray());

        switch ($result->getStatus()) {
            case Status::STATUS_CANCELLED:
                $paymentTransaction->setSuccessful(false)
                    ->setActive(false);
                $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);
                $this->transactionStatusService->persistTransactionStatus($result);

                return str_replace(
                    QueryConstant::PAYMENT_ID_PLACEHODLER,
                    $paymentId,
                    $this->urlHelper->getCancelUrl($paymentTransaction)
                );
            case Status::STATUS_DECLINED:
            case Status::STATUS_FAILED:
                $paymentTransaction->setSuccessful(false)
                    ->setActive(false);
                $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);
                $this->transactionStatusService->persistTransactionStatus($result);

                return str_replace(
                    QueryConstant::PAYMENT_ID_PLACEHODLER,
                    $paymentId,
                    $this->urlHelper->getFailureUrl($paymentTransaction)
                );
            default:
                $paymentTransaction
                    ->setSuccessful(true)
                    ->setActive(true);
                $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);
                $this->transactionStatusService->persistTransactionStatus($result);

                return str_replace(
                    QueryConstant::PAYMENT_ID_PLACEHODLER,
                    $paymentId,
                    $this->urlHelper->getSuccessUrl($paymentTransaction)
                );
        }
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
            new CreatePaymentV3Request()
        );
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return SubmitPaymentRequestV3
     * @throws \Exception
     */
    private function getSubmitPaymentRequestEntity(PaymentTransaction $paymentTransaction): SubmitPaymentRequestV3
    {
        $requestEntity = $this->populatePaymentRequestEntity(
            $paymentTransaction,
            new SubmitPaymentRequestV3()
        );

        $requestEntity->setPaymentData(new PaymentDataEntity());

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

        $billingAddress = $order->getBillingAddress();
        $customerUser = $order->getCustomerUser();

        $purchaseEntity = new PurchaseEntity();
        $purchaseEntity->setAmount(round((float) $paymentTransaction->getAmount(), 2))
            ->setCurrency($paymentTransaction->getCurrency());

        $shippingCost = $order->getShippingCost();
        if ($shippingCost) {
            $purchaseEntity->setDeliveryFee(round((float) $shippingCost->getValue(), 2));
        }

        $channelEntity = new ChannelEntity();
        $channelEntity
            ->setName(ChannelSet::CHANNEL_OROCOMMERCE)
            ->setSource($this->dataHelper->getCmsVersion())
            ->setType(ChannelTypeSet::ECOMMERCE);

        $requestEntity
            ->setChannel($channelEntity)
            ->setReference($paymentTransaction->getEntityIdentifier())
            ->setPaymentMethod($this->config->getPaymentMethod())
            ->setPaymentVariantId($this->config->getVariantId())
            ->setClientIp($this->customerUserLoggingInfoProvider->getUserLoggingInfo($customerUser)['ipaddress'])
            ->setPluginVersion($this->dataHelper->getPluginVersion())
            ->setPurchase($purchaseEntity)
            ->setCustomer($this->getCustomerEntity($customerUser, $billingAddress))
            ->setCart($this->orderItemHelper->buildCartItemsV3($order))
            ->setBillingAddress($this->populateAddressEntity($billingAddress))
            ->setUrls($this->getUrlsEntity($paymentTransaction));

        // Set Shipping address
        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
            $requestEntity->setShippingAddress($this->populateAddressEntity($shippingAddress));
        }

        // Set company
        $company = $billingAddress->getOrganization();
        if (!empty($company)) {
            $companyEntity = new CompanyEntity();
            $companyEntity->setName($company)
                ->setExternalId($this->session->get('external_id'));

            $requestEntity->setCompany($companyEntity);
        }

        $shippingOptionEntity = $this->getShippingOptionEntity($order);
        if ($shippingOptionEntity) {
            $requestEntity->setShippingOption($shippingOptionEntity);
        }

        return $requestEntity;
    }

    /**
     * Get ShippingOptionEntity.
     *
     * @param Order $order
     * @return null|ShippingOptionEntity
     */
    private function getShippingOptionEntity(Order $order): ?ShippingOptionEntity
    {
        $shippingMethod = $order->getShippingMethod();
        if (!$shippingMethod) {
            return null;
        }

        $shippingName = $this->orderItemHelper->getShippingLabel($shippingMethod);
        $shippingCost = $order->getShippingCost();

        $shippingOptionEntity = new ShippingOptionEntity();
        $shippingOptionEntity->setName($shippingName)
            ->setCarrier($shippingName)
            ->setPrice($shippingCost->getValue())
            ->setTaxAmount(0)
            ->setTaxRate(0);

        $tax = $this->orderItemHelper->getTax($order);
        if ($tax && (float) ($tax->getShipping()->getIncludingTax()) > 0) {
            $shippingInclTax = (float) $tax->getShipping()->getIncludingTax();
            $shippingExclTax = (float) $tax->getShipping()->getExcludingTax();
            $shippingTaxAmount = (float) $tax->getShipping()->getTaxAmount();
            $shippingOptionEntity->setPrice($shippingInclTax)
                ->setTaxAmount($shippingTaxAmount);

            if ($shippingExclTax > 0) {
                $shippingOptionEntity->setTaxRate(
                    round(100 * $shippingTaxAmount / $shippingExclTax, 2)
                );
            }
        }

        return $shippingOptionEntity;
    }


    /**
     * Get Customer Entity.
     *
     * @param CustomerUser|null $customer
     * @param OrderAddress $billingAddress
     * @return CustomerEntity
     */
    private function getCustomerEntity(
        ?CustomerUser $customer,
        OrderAddress $billingAddress
    ): CustomerEntity {
        $customerEntity = new CustomerEntity();
        $customerEntity->setType(self::CUSTOMER_TYPE_PERSON);

        if ($customer) {
            $customerEntity->setEmail($customer->getEmail());
            $birthday = $customer->getBirthday();
            if (null !== $birthday && $birthday->diff(new \DateTime())->y >= self::MAJORITY_YEARS) {
                $customerEntity->setBirthdate($birthday->format('Y-m-d'));
            }
        }

        $company = $billingAddress->getOrganization();
        if (!empty($company)) {
            $customerEntity->setType(self::CUSTOMER_TYPE_ORGANIZATION);
        }

        return $customerEntity;
    }

    /**
     * Get Urls Entity.
     *
     * @param PaymentTransaction $paymentTransaction
     * @return UrlsEntity
     */
    private function getUrlsEntity(PaymentTransaction $paymentTransaction): UrlsEntity
    {
        $urls = new UrlsEntity();
        $urls->setSuccess($this->urlHelper->getSuccessUrl($paymentTransaction))
            ->setFailure($this->urlHelper->getFailureUrl($paymentTransaction))
            ->setCancel($this->urlHelper->getCancelUrl($paymentTransaction))
            ->setNotification($this->urlHelper->getNoticeUrl($paymentTransaction))
            ->setPending($this->urlHelper->getPendingUrl($paymentTransaction));

        return $urls;
    }
}
