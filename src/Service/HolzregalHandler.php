<?php declare(strict_types=1);

namespace The13thHolzregal\Service;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItemFactoryRegistry;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Framework\Routing\StorefrontResponse;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Checkout\Cart\Cart;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */
class HolzregalHandler extends StorefrontController
{
    private LineItemFactoryRegistry $factory;

    private CartService $cartService;

    public function __construct(LineItemFactoryRegistry $factory, CartService $cartService)
    {
        $this->factory = $factory;
        $this->cartService = $cartService;
    }

    /**
     * @Route("/cartAdd", name="frontend.example", methods={"GET", "POST"})
     */
    public function add(Cart $cart, SalesChannelContext $context): StorefrontResponse
    {
        // Create product line item
        $lineItem = $this->factory->create([
            'type' => LineItem::PRODUCT_LINE_ITEM_TYPE, // Results in 'product'
            'referencedId' => 'myExampleId', // this is not a valid UUID, change this to your actual ID!
            'quantity' => 5,
            'payload' => ['key' => 'value']
        ], $context);

        $this->cartService->add($cart, $lineItem, $context);

        return $this->renderStorefront('@Storefront/storefront/base.html.twig');
    }
}