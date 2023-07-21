<?php

namespace The13thHolzregal\Subscriber;

use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Subscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
            CheckoutCartPageLoadedEvent::class  => 'onCheckoutPageLoaded',
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmPageLoaded',
        ];
    }

    /**
     * Handles the stuff, that happens in the product detail page
     *
     * @param ProductPageLoadedEvent $event
     */
    public function onProductPageLoaded (ProductPageLoadedEvent $event)
    {
        //do something, when the product detail page loads
    }

    /**
     * Calls onCheckoutPagesRefresh for handling stuff in all checkout pages
     *
     * @param CheckoutConfirmPageLoadedEvent $event
     */
    public function onCheckoutConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event) : void
    {
        $this->onCheckoutPagesRefresh($event);
    }

    /**
     * Calls onCheckoutPagesRefresh for handling stuff in all checkout pages
     *
     * @param CheckoutCartPageLoadedEvent $event
     */
    public function onCheckoutPageLoaded(CheckoutCartPageLoadedEvent $event) : void
    {
        $this->onCheckoutPagesRefresh($event);
    }

    /**
     * Handles the stuff, that happens in both checkout pages
     *
     * @param CheckoutCartPageLoadedEvent|CheckoutConfirmPageLoadedEvent $event
     */
    private function onCheckoutPagesRefresh ($event)
    {
        //do something in the checkout

    }


}
