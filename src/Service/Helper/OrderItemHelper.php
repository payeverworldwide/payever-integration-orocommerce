<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Helper;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Provider\SurchargeProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Payever\Sdk\Payments\Http\MessageEntity\CartItemV3Entity;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class OrderItemHelper
{
    // Properties
    public const PROP_ITEM_ID = 'item_id';
    public const PROP_SKU = 'sku';
    public const PROP_REFERENCE = 'reference';
    public const PROP_TYPE = 'type';
    public const PROP_QUANTITY = 'quantity';
    public const PROP_NAME = 'name';
    public const PROP_UNIT_PRICE_INCL_TAX = 'unit_price_incl_tax';
    public const PROP_UNIT_PRICE_EXCL_TAX = 'unit_price_excl_tax';
    public const PROP_TOTAL_PRICE_INCL_TAX = 'total_price_incl_tax';
    public const PROP_TAX_RATE = 'tax_rate';
    public const PROP_THUMBNAIL = 'thumbnail';
    public const PROP_DESCRIPTION = 'description';
    public const PROP_PRODUCT_URL = 'product_url';

    // Item types
    public const TYPE_PRODUCT = 'product';
    public const TYPE_DISCOUNT = 'discount';
    public const TYPE_SHIPPING = 'shipping';
    public const TYPE_PAYMENT_FEE = 'fee';

    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @var ProductHelper
     */
    private ProductHelper $productHelper;

    /**
     * @var TaxProviderRegistry
     */
    private TaxProviderRegistry $taxProviderRegistry;

    /**
     * @var SurchargeProvider
     */
    private SurchargeProvider $surchargeProvider;

    private ShippingMethodProviderInterface $shippingMethodProvider;


    /**
     * Constructor.
     *
     * @param TranslatorInterface $translator
     * @param ProductHelper $productHelper
     * @param TaxProviderRegistry $taxProviderRegistry
     * @param SurchargeProvider $surchargeProvider
     * @param ShippingMethodProviderInterface $shippingMethodProvider
     */
    public function __construct(
        TranslatorInterface $translator,
        ProductHelper $productHelper,
        TaxProviderRegistry $taxProviderRegistry,
        SurchargeProvider $surchargeProvider,
        ShippingMethodProviderInterface $shippingMethodProvider
    ) {
        $this->translator = $translator;
        $this->productHelper = $productHelper;
        $this->taxProviderRegistry = $taxProviderRegistry;
        $this->surchargeProvider = $surchargeProvider;
        $this->shippingMethodProvider = $shippingMethodProvider;
    }

    /**
     * Get order items array.
     *
     * @param Order $order
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getOrderItems(Order $order): array
    {
        $orderItems = [];
        $orderLines = $order->getLineItems();

        foreach ($orderLines as $key => $orderLine) {
            /** @var OrderLineItem $orderLine */
            $priceInclTax = $orderLine->getValue();
            $priceExclTax = $orderLine->getValue();
            $taxRate = 0;
            $tax = $this->getTax($orderLine);
            if ($tax) {
                $taxRow = $tax->getUnit();
                $priceInclTax = (float) $taxRow->getIncludingTax();
                $priceExclTax = (float) $taxRow->getExcludingTax();
                $taxAmount = (float) $taxRow->getTaxAmount();
                $taxRate = $priceExclTax > 0 ?
                    round(100 * (float) $taxAmount / (float) $priceExclTax, 2) : 0;
            }

            $product = $orderLine->getProduct();
            $qty = $orderLine->getQuantity();
            $sku = preg_replace('#[^0-9a-z_]+#i', '-', $orderLine->getProductSku());

            $orderItems[] = [
                self::PROP_ITEM_ID              => $key,
                self::PROP_SKU                  => $sku,
                self::PROP_REFERENCE            => (string) $orderLine->getEntityIdentifier(),
                self::PROP_TYPE                 => self::TYPE_PRODUCT,
                self::PROP_NAME                 => $orderLine->getProductName(),
                self::PROP_UNIT_PRICE_INCL_TAX  => round($priceInclTax, 2),
                self::PROP_UNIT_PRICE_EXCL_TAX  => round($priceExclTax, 2),
                self::PROP_TOTAL_PRICE_INCL_TAX => round($priceInclTax * $qty, 2),
                self::PROP_TAX_RATE             => $taxRate,
                self::PROP_QUANTITY             => $qty,
                self::PROP_DESCRIPTION          => (string) $product->getDescription()->getText(),
                self::PROP_THUMBNAIL            => $this->productHelper->getImageUrl(
                    $product,
                    ProductImageType::TYPE_LISTING
                ),
                self::PROP_PRODUCT_URL          => $this->productHelper->getProductUrl($product),
            ];
        }

        $surcharge = $this->surchargeProvider->getSurcharges($order);

        // Add shipping
        if ($surcharge->getShippingAmount() > 0) {
            $priceInclTax = $surcharge->getShippingAmount();
            $priceExclTax = $surcharge->getShippingAmount();
            $taxRate = 0;
            $tax = $this->getTax($order);
            if ($tax && (float) $tax->getShipping()->getIncludingTax() > 0) {
                $priceInclTax = (float) $tax->getShipping()->getIncludingTax();
                $priceExclTax = (float) $tax->getShipping()->getExcludingTax();
                $taxAmount = (float) $tax->getShipping()->getTaxAmount();
                $taxRate = $priceExclTax > 0 ?
                    round(100 * (float) $taxAmount / (float) $priceExclTax, 2) : 0;
            }

            $orderItems[] = [
                self::PROP_ITEM_ID              => count($orderItems) + 2,
                self::PROP_SKU                  => 'shipping',
                self::PROP_REFERENCE            => 'shipping',
                self::PROP_TYPE                 => self::TYPE_SHIPPING,
                self::PROP_NAME                 => $this->translator->trans('oro.order.subtotals.shipping_cost'),
                self::PROP_UNIT_PRICE_INCL_TAX  => round($priceInclTax, 2),
                self::PROP_UNIT_PRICE_EXCL_TAX  => round($priceExclTax, 2),
                self::PROP_TOTAL_PRICE_INCL_TAX => round($priceInclTax, 2),
                self::PROP_TAX_RATE             => $taxRate,
                self::PROP_QUANTITY             => 1,
                self::PROP_DESCRIPTION          => null,
                self::PROP_THUMBNAIL            => null,
                self::PROP_PRODUCT_URL          => null,
            ];
        }

        // Add payment fee
        $handlingAmount = $surcharge->getHandlingAmount();
        if ($handlingAmount > 0) {
            $orderItems[] = [
                self::PROP_ITEM_ID              => count($orderItems) + 1,
                self::PROP_SKU                  => 'handling-fee',
                self::PROP_REFERENCE            => 'handling-fee',
                self::PROP_TYPE                 => self::TYPE_PAYMENT_FEE,
                self::PROP_NAME                 => $this->translator->trans('Payment fee'),
                self::PROP_UNIT_PRICE_INCL_TAX  => round($handlingAmount, 2),
                self::PROP_UNIT_PRICE_EXCL_TAX  => round($handlingAmount, 2),
                self::PROP_TOTAL_PRICE_INCL_TAX => round($handlingAmount, 2),
                self::PROP_TAX_RATE             => 0,
                self::PROP_QUANTITY             => 1,
                self::PROP_DESCRIPTION          => null,
                self::PROP_THUMBNAIL            => null,
                self::PROP_PRODUCT_URL          => null,
            ];
        }

        // Add discount
        $discountAmount = $surcharge->getDiscountAmount();
        if (abs($discountAmount) > 0) {
            $orderItems[] = [
                self::PROP_ITEM_ID              => count($orderItems) + 2,
                self::PROP_SKU                  => 'discount',
                self::PROP_REFERENCE            => 'discount',
                self::PROP_TYPE                 => self::TYPE_DISCOUNT,
                self::PROP_NAME                 => $this->translator->trans('oro.order.subtotals.discount'),
                self::PROP_UNIT_PRICE_INCL_TAX  => round($discountAmount, 2),
                self::PROP_UNIT_PRICE_EXCL_TAX  => round($discountAmount, 2),
                self::PROP_TOTAL_PRICE_INCL_TAX => round($discountAmount, 2),
                self::PROP_TAX_RATE             => 0,
                self::PROP_QUANTITY             => 1,
                self::PROP_DESCRIPTION          => null,
                self::PROP_THUMBNAIL            => null,
                self::PROP_PRODUCT_URL          => null,
            ];
        }

        return $orderItems;
    }

    /**
     * Get Order items for payment processing.
     * It excludes shipping and payment fees.
     *
     * @param Order $order
     *
     * @return CartItemV3Entity[]
     */
    public function buildCartItems(Order $order): array
    {
        $orderItems = $this->getOrderItems($order);

        /** @var CartItemV3Entity[] $cartItems */
        $result = [];
        foreach ($orderItems as $item) {
            if (self::TYPE_SHIPPING === $item['type']) {
                continue;
            }

            $cartItem = [
                'name' => $item[self::PROP_NAME],
                'quantity' => $item[self::PROP_QUANTITY],
                'price' => $item[self::PROP_UNIT_PRICE_INCL_TAX],
                'priceNetto' => $item[self::PROP_UNIT_PRICE_EXCL_TAX],
                'identifier' => $item[self::PROP_SKU],
                'sku' => $item[self::PROP_SKU],
                'vatRate' => $item[self::PROP_TAX_RATE],
            ];

            if (self::TYPE_PRODUCT === $item['type']) {
                $cartItem['description'] = $item[self::PROP_DESCRIPTION];
                $cartItem['thumbnail'] = $item[self::PROP_THUMBNAIL];
                $cartItem['url'] = $item[self::PROP_PRODUCT_URL];
            }

            $result[] = $cartItem;
        }

        return $result;
    }

    /**
     * Get Order items for payment processing.
     * It excludes shipping and payment fees.
     *
     * @param Order $order
     *
     * @return CartItemV3Entity[]
     */
    public function buildCartItemsV3(Order $order): array
    {
        $orderItems = $this->getOrderItems($order);

        /** @var CartItemV3Entity[] $cartItems */
        $result = [];
        foreach ($orderItems as $item) {
            if (self::TYPE_SHIPPING === $item['type']) {
                continue;
            }

            $totalTaxAmount = $item[self::PROP_QUANTITY] *
                ($item[self::PROP_UNIT_PRICE_INCL_TAX] - $item[self::PROP_UNIT_PRICE_EXCL_TAX]);

            $cartItem = new CartItemV3Entity();
            $cartItem->setName($item[self::PROP_NAME])
                ->setIdentifier($item[self::PROP_SKU])
                ->setSku(preg_replace('#[^0-9a-z_]+#i', '-', $item[self::PROP_SKU]))
                // Unit price of product item. Include VAT, exclude discount
                ->setUnitPrice($item[self::PROP_UNIT_PRICE_INCL_TAX])
                // The percentage value of tax on product item
                ->setTaxRate($item[self::PROP_TAX_RATE])
                // Total amount of product item, include VAT and discount
                ->setTotalAmount($item[self::PROP_TOTAL_PRICE_INCL_TAX])
                // Total tax amount on product item
                ->setTotalTaxAmount(round($totalTaxAmount, 2))
                ->setQuantity($item[self::PROP_QUANTITY])
                ->setDescription($item[self::PROP_DESCRIPTION]);

            if (self::TYPE_PRODUCT === $item[self::PROP_TYPE]) {
                $cartItem->setThumbnail($item[self::PROP_THUMBNAIL])
                    ->setProductUrl($item[self::PROP_PRODUCT_URL]);
            }

            $result[] = $cartItem;
        }

        return $result;
    }

    /**
     * Gets tax row model for a given order line
     *
     * @param OrderLineItem|Order $orderLine
     *
     * @return Result|null
     */
    public function getTax($orderLine): ?Result
    {
        try {
            $tax = $this->taxProviderRegistry->getEnabledProvider()->getTax($orderLine);
        } catch (TaxationDisabledException $exception) {
            $tax = null;
        }

        return $tax;
    }

    /**
     * Get Shipping Label.
     *
     * @param string $identifier
     * @return string
     */
    public function getShippingLabel(string $identifier): string
    {
        $shippingMethod = $this->shippingMethodProvider->getShippingMethod($identifier);
        if (!$shippingMethod) {
            return $this->translator->trans('oro.order.subtotals.shipping_cost');
        }

        return $shippingMethod->getLabel();
    }
}
