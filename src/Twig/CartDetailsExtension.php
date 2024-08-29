<?php

namespace Payever\Bundle\PaymentBundle\Twig;

use Payever\Bundle\PaymentBundle\Method\Provider\FinanceExpress\ProductDetailProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provide twig functions to work with product details: `payever_cart_details`
 */
class CartDetailsExtension extends AbstractExtension implements ServiceSubscriberInterface
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
            new TwigFunction('payever_cart_details', [$this, 'getCartDetails']),
        ];
    }

    /**
     * @param ShoppingList $shoppingList
     * @return array
     */
    public function getCartDetails($shoppingList): array
    {
        return $shoppingList instanceof ShoppingList
            ? $this->getProductDetailProvider()->getShoppingListData($shoppingList)
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
