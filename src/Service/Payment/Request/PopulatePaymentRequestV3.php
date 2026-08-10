<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Payment\Request;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserLoggingInfoProvider;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Payever\Bundle\PaymentBundle\Constant\LanguageConstant;
use Payever\Bundle\PaymentBundle\Constant\QueryConstant;
use Payever\Bundle\PaymentBundle\Constant\SalutationConstant;
use Payever\Bundle\PaymentBundle\Method\Config\PayeverConfigInterface;
use Payever\Bundle\PaymentBundle\Service\Api\ServiceProvider;
use Payever\Bundle\PaymentBundle\Service\Helper\DataHelper;
use Payever\Bundle\PaymentBundle\Service\Helper\OrderItemHelper;
use Payever\Bundle\PaymentBundle\Service\Helper\UrlHelper;
use Payever\Sdk\Core\Enum\ChannelSet;
use Payever\Sdk\Core\Enum\ChannelTypeSet;
use Payever\Sdk\Core\Http\RequestEntity;
use Payever\Sdk\Payments\Enum\Status;
use Payever\Sdk\Payments\Http\MessageEntity\ChannelEntity;
use Payever\Sdk\Payments\Http\MessageEntity\CompanyEntity;
use Payever\Sdk\Payments\Http\MessageEntity\CustomerAddressEntity as AddressEntity;
use Payever\Sdk\Payments\Http\MessageEntity\CustomerEntity;
use Payever\Sdk\Payments\Http\MessageEntity\PaymentDataEntity;
use Payever\Sdk\Payments\Http\MessageEntity\PurchaseEntity;
use Payever\Sdk\Payments\Http\MessageEntity\ShippingOptionEntity;
use Payever\Sdk\Payments\Http\MessageEntity\SubmitPaymentResultEntity;
use Payever\Sdk\Payments\Http\MessageEntity\UrlsEntity;
use Payever\Sdk\Payments\Http\RequestEntity\CreatePaymentV3Request;
use Payever\Sdk\Payments\Http\RequestEntity\SubmitPaymentRequestV3;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class PopulatePaymentRequestV3
{
    public const CHECKOUT_REFERENCE_PREFIX = 'checkout_';
    private const MAJORITY_YEARS = 18;
    private const CUSTOMER_TYPE_PERSON = 'person';
    private const CUSTOMER_TYPE_ORGANIZATION = 'organization';

    private ServiceProvider $serviceProvider;

    private ConfigManager $configManager;

    private DataHelper $dataHelper;

    private UrlHelper $urlHelper;

    private OrderItemHelper $orderItemHelper;

    private CustomerUserLoggingInfoProvider $customerUserLoggingInfoProvider;

    private LocalizationHelper $localizationHelper;

    private TotalProcessorProvider $totalsProvider;

    /**
     * @var PayeverConfigInterface
     */
    private PayeverConfigInterface $config;

    /**
     * @var Checkout
     */
    private Checkout $checkout;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @param ServiceProvider $serviceProvider
     * @param ConfigManager $configManager
     * @param DataHelper $dataHelper
     * @param UrlHelper $urlHelper
     * @param OrderItemHelper $orderItemHelper
     * @param CustomerUserLoggingInfoProvider $customerUserLoggingInfoProvider
     * @param TotalProcessorProvider $totalsProvider
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(
        ServiceProvider $serviceProvider,
        ConfigManager $configManager,
        DataHelper $dataHelper,
        UrlHelper $urlHelper,
        OrderItemHelper $orderItemHelper,
        CustomerUserLoggingInfoProvider $customerUserLoggingInfoProvider,
        TotalProcessorProvider $totalsProvider,
        LocalizationHelper $localizationHelper,
    ) {
        $this->serviceProvider = $serviceProvider;
        $this->configManager = $configManager;
        $this->dataHelper = $dataHelper;
        $this->urlHelper = $urlHelper;
        $this->orderItemHelper = $orderItemHelper;
        $this->customerUserLoggingInfoProvider = $customerUserLoggingInfoProvider;
        $this->totalsProvider = $totalsProvider;
        $this->localizationHelper = $localizationHelper;
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
     * Set Payment Checkout.
     *
     * @param Checkout $checkout
     *
     * @return $this
     */
    public function setCheckout(Checkout $checkout): self
    {
        $this->checkout = $checkout;

        return $this;
    }

    /**
     * @param Order $order
     *
     * @return string
     * @throws \Exception
     */
    public function createRedirectUrl(Order $order): string
    {
        $paymentRequestEntity = $this->getCreatePaymentRequestEntity($order);

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
     * @param Order $order
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function createSubmitUrl(Order $order): string
    {
        $paymentRequestEntity = $this->getSubmitPaymentRequestEntity($order);

        $response = $this->serviceProvider
            ->getPaymentsApiClient()
            ->submitPaymentRequestV3($paymentRequestEntity);

        /** @var SubmitPaymentResultEntity $result */
        $result = $response->getResponseEntity()->getResult();

        return match ($result->getStatus()) {
            Status::STATUS_CANCELLED => $this->urlHelper->getCancelUrl(
                $this->checkout->getId(),
                [QueryConstant::PARAMETER_PAYMENT_ID => $result->getId()]
            ),
            Status::STATUS_DECLINED, Status::STATUS_FAILED => $this->urlHelper->getFailureUrl(
                $this->checkout->getId(),
                [QueryConstant::PARAMETER_PAYMENT_ID => $result->getId()]
            ),
            default => $this->urlHelper->getSuccessUrl(
                $this->checkout->getId(),
                [QueryConstant::PARAMETER_PAYMENT_ID => $result->getId()]
            ),
        };
    }

    /**
     * @param Order $order
     *
     * @return RequestEntity
     *
     * @throws \Exception
     */
    private function getCreatePaymentRequestEntity(Order $order): RequestEntity
    {
        return $this->populatePaymentRequestEntity(
            $order,
            new CreatePaymentV3Request()
        );
    }

    /**
     * @param Order $order
     *
     * @return SubmitPaymentRequestV3
     *
     * @throws \Exception
     */
    private function getSubmitPaymentRequestEntity(Order $order): SubmitPaymentRequestV3
    {
        $requestEntity = $this->populatePaymentRequestEntity(
            $order,
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
     * @param Order $order
     * @param RequestEntity $requestEntity
     *
     * @return RequestEntity
     * @throws \Exception
     */
    private function populatePaymentRequestEntity(
        Order $order,
        RequestEntity $requestEntity
    ): RequestEntity {
        $billingAddress = $order->getBillingAddress();
        $customerUser = $order->getCustomerUser();

        $this->totalsProvider->enableRecalculation();
        $total = $this->totalsProvider->getTotal($order);

        $purchaseEntity = new PurchaseEntity();
        $purchaseEntity
            ->setAmount(round($total->getAmount(), 2))
            ->setCurrency($total->getCurrency());

        $shippingCost = $order->getShippingCost();
        if ($shippingCost) {
            $purchaseEntity->setDeliveryFee(round((float) $shippingCost->getValue(), 2));
        }

        $channelEntity = new ChannelEntity();
        $channelEntity
            ->setName(ChannelSet::CHANNEL_OROCOMMERCE)
            ->setSource($this->dataHelper->getCmsVersion())
            ->setType(ChannelTypeSet::ECOMMERCE);

        $reference = $order->getId() ?: self::CHECKOUT_REFERENCE_PREFIX . $this->checkout->getId();

        $requestEntity
            ->setChannel($channelEntity)
            ->setReference($reference)
            ->setPaymentMethod($this->config->getPaymentMethod())
            ->setVariantId($this->config->getVariantId())
            ->setClientIp($this->customerUserLoggingInfoProvider->getUserLoggingInfo($customerUser)['ipaddress'])
            ->setPluginVersion($this->dataHelper->getPluginVersion())
            ->setPurchase($purchaseEntity)
            ->setCustomer($this->getCustomerEntity($customerUser, $billingAddress))
            ->setCart($this->orderItemHelper->buildCartItemsV3($order))
            ->setBillingAddress($this->populateAddressEntity($billingAddress))
            ->setUrls($this->getUrlsEntity());

        // Set Shipping address
        if ($this->config->getShippingAddressAllowed()) {
            $shippingAddress = $order->getShippingAddress();
            if ($shippingAddress) {
                $requestEntity->setShippingAddress($this->populateAddressEntity($shippingAddress));
            }

            $shippingOptionEntity = $this->getShippingOptionEntity($order);
            if ($shippingOptionEntity) {
                $requestEntity->setShippingOption($shippingOptionEntity);
            }
        }

        $language = $this->getLanguage();
        if ($language) {
            $requestEntity->setLocale($language);
        }

        // Set company
        $company = $billingAddress->getOrganization();
        if (!empty($company)) {
            $companyEntity = new CompanyEntity();
            $companyEntity
                ->setName($company)
                ->setExternalId($billingAddress->getPayeverExternalId());

            $requestEntity->setCompany($companyEntity);
        }

        // Add Company name to payment data
        $paymentData = new PaymentDataEntity();
        $paymentData->setForceRedirect($this->config->getIsRedirectMethod());

        $requestEntity->setPaymentData($paymentData);

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

        $shippingName = $this->orderItemHelper->getShippingLabel($shippingMethod) ?: 'Carrier';
        $shippingCost = $order->getShippingCost();

        $shippingOptionEntity = new ShippingOptionEntity();
        $shippingOptionEntity
            ->setName((string)$shippingName)
            ->setCarrier((string)$shippingName)
            ->setPrice((float)$shippingCost->getValue())
            ->setTaxAmount(0)
            ->setTaxRate(0);

        /** @var \Oro\Bundle\TaxBundle\Model\Result $tax */
        $tax = $this->orderItemHelper->getTax($order);

        if ($tax && (float) ($tax->getShipping()->getIncludingTax()) > 0) {
            // @codeCoverageIgnoreStart
            // $tax final class
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
            // @codeCoverageIgnoreEnd
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
        $customerEntity->setPhone($billingAddress->getPhone());

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
     * @return UrlsEntity
     */
    private function getUrlsEntity(): UrlsEntity
    {
        $urls = new UrlsEntity();
        $urls
            ->setSuccess($this->urlHelper->getSuccessUrl($this->checkout->getId()))
            ->setPending($this->urlHelper->getPendingUrl($this->checkout->getId()))
            ->setFailure($this->urlHelper->getFailureUrl($this->checkout->getId()))
            ->setCancel($this->urlHelper->getCancelUrl($this->checkout->getId()))
            ->setNotification($this->urlHelper->getNoticeUrl());

        return $urls;
    }

    /**
     * Get checkout language
     *
     * @return string
     */
    private function getLanguage(): string
    {
        $checkoutLng = $this->configManager->get('payever_payment.checkout_language') ?: LanguageConstant::STORE;

        switch ($checkoutLng) {
            case LanguageConstant::NONE:
                return isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])
                    ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2)
                    : '';
            case LanguageConstant::STORE:
                $currentLocalization = $this->localizationHelper->getCurrentLocalization();

                return substr($currentLocalization->getLanguageCode(), 0, 2);
            default:
                return $checkoutLng;
        }
    }
}
