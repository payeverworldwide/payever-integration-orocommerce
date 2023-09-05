<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Helper;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Provider\SurchargeProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use Payever\Bundle\PaymentBundle\Constant\QueryConstant;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
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
     * @var AttachmentManager
     */
    private AttachmentManager $attachmentManager;

    /**
     * @var TaxProviderRegistry
     */
    private TaxProviderRegistry $taxProviderRegistry;

    /**
     * @var SurchargeProvider
     */
    private SurchargeProvider $surchargeProvider;

    public function __construct(
        TranslatorInterface $translator,
        AttachmentManager $attachmentManager,
        TaxProviderRegistry $taxProviderRegistry,
        SurchargeProvider $surchargeProvider,
    ) {
        $this->translator = $translator;
        $this->attachmentManager = $attachmentManager;
        $this->taxProviderRegistry = $taxProviderRegistry;
        $this->surchargeProvider = $surchargeProvider;
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
                self::PROP_THUMBNAIL            => $this->getImageUrl($product),
                self::PROP_PRODUCT_URL          => null,
            ];

            // @todo Add url
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
     * @return array
     */
    public function buildCartItems(Order $order): array
    {
        $orderItems = $this->getOrderItems($order);

        $result = [];
        foreach ($orderItems as $item) {
            if (self::TYPE_SHIPPING === $item['type']) {
                continue;
            }

            $cartItem = [
                'name' => utf8_encode($item[self::PROP_NAME]),
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
     * @param Product $product
     *
     * @return null|string
     */
    private function getImageUrl(Product $product): ?string
    {
        $image = $product->getImagesByType('listing')->first();
        if (!$image) {
            return null;
        }

        return $this->attachmentManager->getFilteredImageUrl(
            $image->getImage(),
            'product_small',
            AttachmentManager::DEFAULT_FORMAT,
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * Gets tax row model for a given order line
     *
     * @param OrderLineItem|Order $orderLine
     *
     * @return Result|null
     */
    private function getTax($orderLine): ?Result
    {
        try {
            $tax = $this->taxProviderRegistry->getEnabledProvider()->getTax($orderLine);
        } catch (TaxationDisabledException $exception) {
            $tax = null;
        }

        return $tax;
    }
}
