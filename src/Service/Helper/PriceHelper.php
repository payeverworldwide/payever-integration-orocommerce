<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Helper;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
#[Autoconfigure(lazy: true)]
class PriceHelper
{
    /** @var WebsiteManager */
    private WebsiteManager $websiteManager;

    /** @var UserCurrencyManager */
    private UserCurrencyManager $userCurrencyManager;

    /**
     * @var ProductPriceProviderInterface
     */
    private ProductPriceProviderInterface $productPriceProvider;
    /**
     * @var ProductPriceScopeCriteriaRequestHandler
     */
    private ProductPriceScopeCriteriaRequestHandler $priceScopeCriteriaRequestHandler;
    /**
     * @var ProductPriceCriteriaFactoryInterface
     */
    private $productPriceCriteriaFactory;

    /**
     * @param ProductPriceProviderInterface $productPriceProvider
     * @param ProductPriceScopeCriteriaRequestHandler $priceScopeCriteriaRequestHandler
     */
    public function __construct(
        WebsiteManager $websiteManager,
        UserCurrencyManager $userCurrencyManager,
        ProductPriceProviderInterface $productPriceProvider,
        ProductPriceScopeCriteriaRequestHandler $priceScopeCriteriaRequestHandler
    ) {
        $this->websiteManager = $websiteManager;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->productPriceProvider = $productPriceProvider;
        $this->priceScopeCriteriaRequestHandler = $priceScopeCriteriaRequestHandler;
    }

    public function setProductPriceCriteriaFactory(
        ?ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory
    ): void {
        $this->productPriceCriteriaFactory = $productPriceCriteriaFactory;
    }

    /**
     * Get Product Price.
     *
     * @see https://doc.oroinc.com/bundles/commerce/PricingBundle/getting-product-price/
     * @param Product $product
     * @param ProductUnit|null $productUnit
     * @param float $quantity
     * @param string|null $currency
     * @return Price|null
     */
    public function getPrice(Product $product, ?ProductUnit $productUnit, float $quantity, ?string $currency): ?Price
    {
        if (!$currency) {
            $website = $this->websiteManager->getCurrentWebsite();
            $currency = $this->userCurrencyManager->getUserCurrency($website);
        }

        $productPriceCriteria = $this->getProductPriceCriteria($product, $productUnit, $quantity, $currency);
        $priceScopeCriteria = $this->priceScopeCriteriaRequestHandler->getPriceScopeCriteria();
        $matchedPrices = $this->productPriceProvider->getMatchedPrices(
            [$productPriceCriteria],
            $priceScopeCriteria
        );

        return $matchedPrices[$productPriceCriteria->getIdentifier()] ?? null;
    }

    /**
     * @param Product $product
     * @param ProductUnit|null $productUnit
     * @param float $quantity
     * @param string $currency
     * @return ProductPriceCriteria
     */
    private function getProductPriceCriteria(
        Product $product,
        ?ProductUnit $productUnit,
        float $quantity,
        string $currency
    ): ProductPriceCriteria {
        if (!$this->productPriceCriteriaFactory) {
            // Failback
            return new ProductPriceCriteria($product, $productUnit, $quantity, $currency);
        }

        return $this->productPriceCriteriaFactory->create(
            $product,
            $productUnit,
            $quantity,
            $currency
        );
    }
}
