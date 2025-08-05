<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Method\Provider\FinanceExpress;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Payever\Bundle\PaymentBundle\Service\Helper\ProductHelper;
use Payever\Bundle\PaymentBundle\Service\Helper\PriceHelper;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

#[Autoconfigure(lazy: true)]
class ProductDetailProvider
{
    /**
     * @var ProductHelper
     */
    private ProductHelper $productHelper;

    /**
     * @var PriceHelper
     */
    private PriceHelper $priceHelper;

    /**
     * Constructor.
     *
     * @param ProductHelper $productHelper
     * @param PriceHelper $priceHelper
     */
    public function __construct(
        ProductHelper $productHelper,
        PriceHelper $priceHelper
    ) {
        $this->productHelper = $productHelper;
        $this->priceHelper = $priceHelper;
    }

    /**
     * Get product detail for single Product entity.
     *
     * @param Product $product
     * @param Localization|null $localization
     *
     * @return array
     */
    public function getProductData(Product $product, ?Localization $localization = null): array
    {
        if (!$product->getSku()) {
            return [];
        }

        $name = $product->getName($localization);
        if (!$name || !$name->getString()) {
            return [];
        }

        $price = $this->priceHelper->getPrice(
            $product,
            $this->productHelper->getProductUnit($product),
            1,
            null
        );

        return [
            'identifier' => $product->getSku(),
            'name' => $name->getString(),
            'price' => $price,
            'amount' => $price,
            'quantity' => 1,
            'thumbnail' => $this->productHelper->getImageUrl($product),
            'unit' => 'EACH'
        ];
    }

    /**
     * Get product details of Shopping List.
     *
     * @param ShoppingList $shoppingList
     * @return array
     */
    public function getShoppingListData(ShoppingList $shoppingList): array
    {
        $result = [];
        $lineItems = $shoppingList->getLineItems();

        /** @var LineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            $product = $lineItem->getProduct();
            $qty = $lineItem->getQuantity();
            $price = $this->priceHelper->getPrice($product, $lineItem->getProductUnit(), $qty, null);

            $result[] = [
                'identifier' => $lineItem->getProductSku(),
                'name' => $product->getName()->getString(),
                'price' => $price->getValue(),
                'amount' => $qty * $price->getValue(),
                'quantity' => $qty,
                'thumbnail' => $this->productHelper->getImageUrl($product),
                'unit' => 'EACH'
            ];
        }

        return $result;
    }
}
