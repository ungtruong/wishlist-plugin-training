<?php declare(strict_types=1);

namespace Wishlist\WishlistPlugin\Core\Checkout\Customer\SalesChannel;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerWishlist\CustomerWishlistEntity;
use Wishlist\WishlistPlugin\Core\Content\WishlistPlugin\WishlistPluginEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

class LoadWishlistRouteResponse extends StoreApiResponse
{
    /**
     * @var WishlistPluginEntity 
     */
    protected $wishlist;

    /**
     * @var EntitySearchResult
     */
    protected $productListing;

    public function __construct(WishlistPluginEntity  $wishlist, EntitySearchResult $listing)
    {
        $this->wishlist = $wishlist;
        $this->productListing = $listing;
        parent::__construct(new ArrayStruct([
            'wishlist' => $wishlist,
            'products' => $listing,
        ], 'wishlist_products'));
    }

    public function getWishlist(): WishlistPluginEntity 
    {
        return $this->wishlist;
    }

    public function setWishlist(WishlistPluginEntity  $wishlist): void
    {
        $this->wishlist = $wishlist;
    }

    public function getProductListing(): EntitySearchResult
    {
        return $this->productListing;
    }

    public function setProductListing(EntitySearchResult $productListing): void
    {
        $this->productListing = $productListing;
    }
}
