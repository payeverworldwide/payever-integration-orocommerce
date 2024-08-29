<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Shipping;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Converter\ShippingPricesConverter;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Payever\Bundle\PaymentBundle\Service\Helper\DataHelper;
use Payever\Bundle\PaymentBundle\Service\Helper\ProductHelper;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class ShippingCostService
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
     * @var ProductHelper
     */
    private ProductHelper $productHelper;

    /**
     * @var DataHelper
     */
    private DataHelper $dataHelper;

    /**
     * @var ShippingPriceProviderInterface
     */
    private ShippingPriceProviderInterface $priceProvider;

    /**
     * @var ShippingPricesConverter
     */
    private ShippingPricesConverter $priceConverter;

    /**
     * @var CheckoutShippingContextProvider
     */
    private CheckoutShippingContextProvider $checkoutShippingContextProvider;

    /**
     * @var MemoryCacheProviderInterface
     */
    private MemoryCacheProviderInterface $memoryCacheProvider;

    /**
     * Constructor.
     *
     * @param ObjectManager $manager
     * @param ManagerRegistry $managerRegistry
     * @param ProductHelper $productHelper
     * @param DataHelper $dataHelper
     * @param ShippingPricesConverter $priceConverter
     * @param ShippingPriceProviderInterface $priceProvider
     * @param CheckoutShippingContextProvider $checkoutShippingContextProvider
     * @param MemoryCacheProviderInterface $memoryCacheProvider
     */
    public function __construct(
        ObjectManager $manager,
        ManagerRegistry $managerRegistry,
        ProductHelper $productHelper,
        DataHelper $dataHelper,
        ShippingPricesConverter $priceConverter,
        ShippingPriceProviderInterface $priceProvider,
        CheckoutShippingContextProvider $checkoutShippingContextProvider,
        MemoryCacheProviderInterface $memoryCacheProvider
    ) {
        $this->manager = $manager;
        $this->managerRegistry = $managerRegistry;
        $this->productHelper = $productHelper;
        $this->dataHelper = $dataHelper;
        $this->priceConverter = $priceConverter;
        $this->priceProvider = $priceProvider;
        $this->checkoutShippingContextProvider = $checkoutShippingContextProvider;
        $this->memoryCacheProvider = $memoryCacheProvider;
    }

    /**
     * Get Shipping Rates.
     *
     * @param CustomerUser $customerUser
     * @param OrderAddress $address
     * @param array $cartItems
     * @param string $currency
     * @param string|null $paymentMethod
     * @return array
     */
    public function getShippingRates(
        CustomerUser $customerUser,
        OrderAddress $address,
        array $cartItems,
        string $currency,
        ?string $paymentMethod
    ): array {
        // Create Checkout
        $checkout = new Checkout();
        $website = $this->dataHelper->getWebsite();

        $checkout->setSource(new CheckoutSource())
            ->setWebsite($website)
            ->setOrganization($website->getOrganization())
            ->setOwner($this->getDefaultUserOwner())
            ->setBillingAddress($address)
            ->setShippingAddress($address)
            ->setCurrency($currency)
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime())
            ->setCompleted(false)
            ->setPaymentMethod($paymentMethod);

        if (!$customerUser->isGuest()) {
            $checkout->setCustomerUser($customerUser);
        }

        // Remove line items if exists
        $lineItems = $checkout->getLineItems();
        foreach ($lineItems as $lineItem) {
            $checkout->removeLineItem($lineItem);
        }

        // Add cart items
        foreach ($cartItems as $lineItem) {
            $product = $this->productHelper->getProduct($lineItem['identifier']);
            if (!$product) {
                throw new \InvalidArgumentException('Product not found: ' . $lineItem['identifier']);
            }

            $checkoutLineItem = new CheckoutLineItem();
            $checkoutLineItem->setProduct($product)
                ->setProductUnit($this->productHelper->getProductUnit($product))
                ->setPrice(Price::create($lineItem['price'], $currency))
                ->setPriceType(OrderLineItem::PRICE_TYPE_UNIT)
                ->setQuantity($lineItem['quantity'])
                ->setCurrency($currency)
                ->setProductSku($lineItem['identifier'])
                ->preSave();

            $checkout->addLineItem($checkoutLineItem);
        }

        $this->manager->persist($checkout);
        $this->manager->flush($checkout);

        // Remove shipping cache
        $this->memoryCacheProvider->reset();

        // Estimate shipping
        $shippingContext = $this->checkoutShippingContextProvider->getContext($checkout);
        $shippingMethodViews = $this->priceProvider->getApplicableMethodsViews($shippingContext)->toArray();
        $result = $this->priceConverter->convertPricesToArray($shippingMethodViews);

        // Drop temporary entities
        $this->manager->remove($checkout);
        $this->manager->flush($checkout);

        return $result;
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
}
