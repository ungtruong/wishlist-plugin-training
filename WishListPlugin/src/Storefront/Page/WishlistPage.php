<?php declare(strict_types=1);

namespace Wishlist\WishlistPlugin\Storefront\Page;

use Wishlist\WishlistPlugin\Core\Checkout\Customer\SalesChannel\LoadWishlistRouteResponse;
use Shopware\Storefront\Page\Page;

class WishlistPage extends Page
{
    /**
     * @var LoadWishlistRouteResponse
     */
    protected $wishlist;

    public function getWishlist(): LoadWishlistRouteResponse
    {
        return $this->wishlist;
    }

    public function setWishlist(LoadWishlistRouteResponse $wishlist): void
    {
        $this->wishlist = $wishlist;
    }
}
