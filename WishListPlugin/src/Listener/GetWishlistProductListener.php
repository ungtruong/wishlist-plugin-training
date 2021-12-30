<?php 
namespace Wishlist\WishlistPlugin\Listener;

use  Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageCriteriaEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Grouping\FieldGrouping;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
Class GetWishlistProductListener 
{
    public function onShopwareStorefrontPageProductProductPageCriteriaEvent(ProductPageCriteriaEvent $entityLoadedEvent) :void
    {
        $criteria = $entityLoadedEvent->getCriteria();
        $criteria->addAssociation('wishlistPlugins');
        $criteria->addAggregation(
            new EntityAggregation('wishlistPlugins', 'wishlistPlugins.customerId', 'wishlist_plugin')
        );
        
    }
}