<?php declare(strict_types=1);

namespace Wishlist\WishlistPlugin\Storefront\Page\Wishlist;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Storefront\Page\Wishlist\GuestWishlistPage;
use Shopware\Storefront\Page\Wishlist\GuestWishlistPageLoadedEvent;
class GuestWishlistPageLoader
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var GenericPageLoaderInterface
     */
    private $genericPageLoader;

    public function __construct(
        GenericPageLoaderInterface $genericPageLoader,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->genericPageLoader = $genericPageLoader;
    }

    public function load(Request $request, SalesChannelContext $context): GuestWishlistPage
    {
        $page = $this->genericPageLoader->load($request, $context);
        $page = GuestWishlistPage::createFrom($page);

        //$this->eventDispatcher->dispatch(new GuestWishlistPageLoadedEvent($page, $context, $request));

        return $page;
    }
}
