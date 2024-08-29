<?php

namespace Payever\Bundle\PaymentBundle\Twig;

use Payever\Bundle\PaymentBundle\Method\Provider\FinanceExpress\ProductDetailProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provide twig functions to work with product details: `payever_product_detail`
 */
class ProductDetailExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('payever_product_detail', [$this, 'getProductDetail']),
        ];
    }

    /**
     * @param Product $product
     * @return array
     */
    public function getProductDetail($product): array
    {
        return $product instanceof Product
            ? $this->getProductDetailProvider()->getProductData($product)
            : [];
    }

    /**
     * @return ProductDetailProvider
     */
    private function getProductDetailProvider(): ProductDetailProvider
    {
        return $this->container->get('payever.provider.finance_express.product_detail');
    }

    /**
     * @return string[]
     */
    public static function getSubscribedServices(): array
    {
        return [
            'payever.provider.finance_express.product_detail' => ProductDetailProvider::class
        ];
    }
}
