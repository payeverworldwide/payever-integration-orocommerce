<?php

declare(strict_types=1);

namespace Payever\Bundle\PaymentBundle\Service\Helper;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\RedirectBundle\DataProvider\CanonicalDataProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProductHelper
{
    /**
     * @var ManagerRegistry
     */
    private ManagerRegistry $managerRegistry;

    /**
     * @var CanonicalDataProvider
     */
    private CanonicalDataProvider $canonicalDataProvider;

    /**
     * @var AttachmentManager
     */
    private AttachmentManager $attachmentManager;

    /**
     * Constructor.
     *
     * @param ManagerRegistry $managerRegistry
     * @param CanonicalDataProvider $canonicalDataProvider
     * @param AttachmentManager $attachmentManager
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        CanonicalDataProvider $canonicalDataProvider,
        AttachmentManager $attachmentManager
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->canonicalDataProvider = $canonicalDataProvider;
        $this->attachmentManager = $attachmentManager;
    }

    /**
     * Get Product.
     *
     * @param string $sku
     * @return Product|null
     */
    public function getProduct(string $sku): ?Product
    {
        return $this->getProductRepository()->findOneBySku($sku);
    }

    /**
     * Get Product Unit.
     *
     * @param Product $product
     * @return ProductUnit|null
     */
    public function getProductUnit(Product $product): ?ProductUnit
    {
        $productUnit = $product->getPrimaryUnitPrecision()->getProductUnit();
        if (!$productUnit) {
            $productUnits = $product->getAvailableUnits();
            return array_shift($productUnits);
        }

        return $productUnit;
    }


    /**
     * Get Product Image Url.
     *
     * @param Product $product
     * @param string $type See ProductImageType::TYPE_LISTING
     * @return string|null
     */
    public function getImageUrl(Product $product, string $type = 'main'): ?string
    {
        $image = $product->getImagesByType($type)->first();
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
     * Get Product Url.
     *
     * @param Product $product
     * @return string
     */
    public function getProductUrl(Product $product): string
    {
        // @todo get Redirect url from `oro_redirect_slug`
        return $this->canonicalDataProvider->getUrl($product);
    }

    /**
     * @return ProductRepository
     */
    private function getProductRepository(): ProductRepository
    {
        return $this->managerRegistry->getRepository(Product::class);
    }
}
