<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Generator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Entity\Repository\RegionRepository;
use Oro\Bundle\AddressBundle\Utils\AddressApiUtils;
use Oro\Bundle\CurrencyBundle\Converter\RateConverterInterface;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Payever\Bundle\PaymentBundle\Service\Helper\DataHelper;

class OrderGenerator extends AddressApiUtils
{
    /**
     * @var ObjectManager
     */
    private ObjectManager $manager;

    /**
     * @var ManagerRegistry
     */
    private ManagerRegistry $managerRegistry;

    /**
     * @var RateConverterInterface
     */
    private RateConverterInterface $rateConverter;

    /**
     * @var DataHelper
     */
    private DataHelper $dataHelper;

    /**
     * Constructor.
     *
     * @param ObjectManager $manager
     * @param ManagerRegistry $managerRegistry
     * @param RateConverterInterface $rateConverter
     * @param DataHelper $dataHelper
     */
    public function __construct(
        ObjectManager $manager,
        ManagerRegistry $managerRegistry,
        RateConverterInterface $rateConverter,
        DataHelper $dataHelper
    ) {
        $this->manager = $manager;
        $this->managerRegistry = $managerRegistry;
        $this->rateConverter = $rateConverter;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Create Order.
     *
     * @param CustomerUser $customerUser
     * @param array $billingAddress
     * @param array $shippingAddress
     * @param string $identifier
     * @param string $currency
     * @param float $total
     * @param float $subtotal
     * @param array $orderLines
     * @param string|null $shippingMethod
     * @param float|null $shippingPrice
     * @return Order
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function createOrder(
        CustomerUser $customerUser,
        array $billingAddress,
        array $shippingAddress,
        string $identifier,
        string $currency,
        float $total,
        float $subtotal,
        array $orderLines,
        ?string $shippingMethod,
        ?float $shippingPrice
    ): Order {
        $orderMetadata = $this->manager->getClassMetadata(Order::class);
        $this->disablePrePersistCallback($orderMetadata);

        $user = $this->getDefaultUserOwner();

        $order = new Order();

        $total = MultiCurrency::create($total, $currency);
        $total->setBaseCurrencyValue($this->rateConverter->getBaseCurrencyAmount($total));

        $subtotal = MultiCurrency::create($subtotal, $currency);
        $subtotal->setBaseCurrencyValue($this->rateConverter->getBaseCurrencyAmount($subtotal));

        $order
            ->setOwner($user)
            ->setCustomer($customerUser->getCustomer())
            ->setIdentifier($identifier)
            ->setCustomerUser($customerUser)
            ->setOrganization($user->getOrganization())
            ->setBillingAddress($this->createOrderAddress($billingAddress))
            ->setShippingAddress($this->createOrderAddress($shippingAddress))
            ->setWebsite($this->dataHelper->getWebsite())
            ->setCurrency($currency)
            ->setTotalObject($total)
            ->setSubtotalObject($subtotal)
            ->setInternalStatus($this->getOrderInternalStatusByName('open'))
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());

        foreach ($orderLines as $orderLine) {
            if (is_array($orderLine)) {
                $orderLine = $this->getOrderLineItem(
                    $orderLine['identifier'],
                    $orderLine['price'],
                    $orderLine['qty'],
                    $currency
                );
            }

            $order->addLineItem($orderLine);
        }

        // Shipping
        if ($shippingMethod) {
            $order->setShippingMethod($shippingMethod)
                ->setEstimatedShippingCostAmount($shippingPrice)
                ->setShippingMethodType('primary');
        }

        $this->manager->persist($order);
        $this->enablePrePersistCallback($orderMetadata);
        $this->manager->flush($order);

        return $order;
    }

    /**
     * Create Order Address.
     *
     * @param array $address
     * @return OrderAddress
     */
    public function createOrderAddress(array $address): OrderAddress
    {
        $orderAddress = new OrderAddress();
        $orderAddress
            ->setLabel($address['label'])
            ->setCountry($this->getCountryByIso2Code($address['country']))
            ->setCity($address['city'])
            ->setRegionText($address['region'])
            ->setStreet($address['street'])
            ->setStreet2($address['street2'])
            ->setPostalCode($address['postalCode'])
            ->setFirstName($address['firstName'])
            ->setLastName($address['lastName'])
            ->setPhone($address['phone']);

        // Set region
        if ($address['region']) {
            $region = $this->resolveRegion($address['country'], $address['region']);
            if ($region) {
                $orderAddress->setRegion($region)
                    ->setRegionText($region->getName());
            }
        }

        $this->manager->persist($orderAddress);

        return $orderAddress;
    }

    /**
     * Get Order Line Item.
     *
     * @param string $identifier
     * @param float $price
     * @param float $quantity
     * @param string $currency
     * @return OrderLineItem
     */
    public function getOrderLineItem(
        string $identifier,
        float $price,
        float $quantity,
        string $currency
    ): OrderLineItem {
        $orderLineItem = new OrderLineItem();
        $price = Price::create($price, $currency);

        $product = $this->getProduct($identifier);
        if (!$product) {
            throw new \InvalidArgumentException('Product not found: ' . $identifier);
        }

        return $orderLineItem
            ->setFromExternalSource(0)
            ->setProduct($product)
            ->setProductName($product->getName())
            ->setProductUnit($this->getProductUnit($product))
            ->setQuantity($quantity)
            ->setPrice($price);
    }

    /**
     * @return ProductRepository
     */
    private function getProductRepository(): ProductRepository
    {
        return $this->managerRegistry->getRepository(Product::class);
    }

    /**
     * @param string $sku
     * @return Product|null
     */
    private function getProduct(string $sku): ?Product
    {
        $repository = $this->getProductRepository();
        if (method_exists($repository, 'findOneBySku')) {
            return $repository->findOneBySku($sku);
        }

        // OroCommerce 6.0+
        return $repository->findOneBy(['sku' => $sku]);
    }

    /**
     * @param Product $product
     * @return ProductUnit|null
     */
    private function getProductUnit(Product $product): ?ProductUnit
    {
        $units = $product->getAvailableUnits();

        return $units ? $units[array_rand($units)] : null;
    }

    /**
     * @param string $iso2Code
     * @return Country|null
     */
    private function getCountryByIso2Code(string $iso2Code): ?Country
    {
        return $this->manager->getReference('OroAddressBundle:Country', $iso2Code);
    }

    /**
     * @param string $name
     * @return object|null
     */
    private function getOrderInternalStatusByName(string $name)
    {
        return $this->manager
            ->getRepository(ExtendHelper::buildEnumValueClassName(Order::INTERNAL_STATUS_CODE))
            ->findOneBy(['id' => $name]);
    }

    /**
     * @return User
     */
    private function getDefaultUserOwner(): User
    {
        $userRepository = $this->managerRegistry
            ->getManagerForClass(User::class)
            ->getRepository(User::class);

        return $userRepository->findOneBy([], ['id' => 'ASC']);
    }

    /**
     * @param ClassMetadata $classMetadata
     * @return void
     */
    private function enablePrePersistCallback(ClassMetadata $classMetadata): void
    {
        $lifecycleCallbacks = $classMetadata->lifecycleCallbacks;
        array_unshift($lifecycleCallbacks['prePersist'], 'prePersist');
        $classMetadata->setLifecycleCallbacks($lifecycleCallbacks);
    }

    /**
     * @param ClassMetadata $classMetadata
     * @return void
     */
    private function disablePrePersistCallback(ClassMetadata $classMetadata): void
    {
        $lifecycleCallbacks = $classMetadata->lifecycleCallbacks;
        $lifecycleCallbacks['prePersist'] = array_diff($lifecycleCallbacks['prePersist'], ['prePersist']);
        $classMetadata->setLifecycleCallbacks($lifecycleCallbacks);
    }

    /**
     * Resolve Region.
     *
     * @param string $countryCode
     * @param string $region
     * @return Region|null
     */
    private function resolveRegion(string $countryCode, string $region): ?Region
    {
        $combinedCode = self::getRegionCombinedCodeByCode($countryCode, $region, $this->manager);
        if (!$combinedCode) {
            $combinedCode = self::getRegionCombinedCodeByName($countryCode, $region, $this->manager);
        }

        if (!$combinedCode) {
            return null;
        }

        /** @var RegionRepository $repository */
        $repository = $this->managerRegistry
            ->getManagerForClass('OroAddressBundle:Region')
            ->getRepository('OroAddressBundle:Region');

        return $repository->findOneBy(['combinedCode' => $combinedCode]);
    }
}
