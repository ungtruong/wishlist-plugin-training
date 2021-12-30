<?php declare(strict_types=1);

namespace Wishlist\WishlistPlugin\Storefront\Page\Wishlist;

use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerWishlist\CustomerWishlistEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\CustomerWishlistNotFoundException;
use Wishlist\WishlistPlugin\Core\Checkout\Customer\SalesChannel\AbstractLoadWishlistRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\LoadWishlistRouteResponse;
use Shopware\Core\Content\Category\Exception\CategoryNotFoundException;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Storefront\Page\Wishlist\WishlistPageProductCriteriaEvent;
use Wishlist\WishlistPlugin\Storefront\Page\WishlistPage;
use Shopware\Storefront\Page\Wishlist\WishlistPageLoadedEvent;

class WishlistPageLoader
{
    private const LIMIT = 3;

    private const DEFAULT_PAGE = 1;

    private GenericPageLoaderInterface $genericLoader;

    private EventDispatcherInterface $eventDispatcher;

    private AbstractLoadWishlistRoute $wishlistLoadRoute;

    public function __construct(
        GenericPageLoaderInterface $genericLoader,
        AbstractLoadWishlistRoute $wishlistLoadRoute,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->genericLoader = $genericLoader;
        $this->wishlistLoadRoute = $wishlistLoadRoute;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @throws CategoryNotFoundException
     * @throws CustomerNotLoggedInException
     * @throws InconsistentCriteriaIdsException
     * @throws MissingRequestParameterException
     */
    public function load(Request $request, SalesChannelContext $context, CustomerEntity $customer): WishlistPage
    {
        $criteria = $this->createCriteria($request);
        //$this->eventDispatcher->dispatch(new WishlistPageProductCriteriaEvent($criteria, $context, $request));

        $page = $this->genericLoader->load($request, $context);
        $page = WishlistPage::createFrom($page);

        //$wishlistId = $request->wishlistid;
        $attributes = $request->attributes->all();
        $wishlistId = $attributes['wishlistId'];
        
        try {
            $page->setWishlist($this->wishlistLoadRoute->load($request, $context, $criteria, $customer, $wishlistId));
        } catch (CustomerWishlistNotFoundException $exception) {
            $page->setWishlist(
                new LoadWishlistRouteResponse(
                    new CustomerWishlistEntity(),
                    new EntitySearchResult(
                        'wishlist',
                        0,
                        new ProductCollection(),
                        null,
                        $criteria,
                        $context->getContext()
                    )
                )
            );
        }

        // $this->eventDispatcher->dispatch(
        //     new WishlistPageLoadedEvent($page, $context, $request)
        // );

        return $page;
    }

    private function createCriteria(Request $request): Criteria
    {
        $limit = $request->query->get('limit');
        $limit = $limit ? (int) $limit : self::LIMIT;
        $limit = $this->wishlistLoadRoute->getItemPerPage();
        $page = $request->query->get('p');
        $page = $page ? (int) $page : self::DEFAULT_PAGE;
        $offset = $limit * ($page - 1);

        return (new Criteria())
            ->addSorting(new FieldSorting('wishlists.updatedAt', FieldSorting::ASCENDING))
            ->addAssociation('manufacturer')
            ->addAssociation('options.group')
            ->setLimit($limit)
            ->setOffset($offset)
            ->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);
    }
    /**
     * @throws CategoryNotFoundException
     * @throws CustomerNotLoggedInException
     * @throws InconsistentCriteriaIdsException
     */
    public function loadAnotherWishlist(Request $request, SalesChannelContext $context, CustomerEntity $customer)
    {
        $attributes = $request->attributes->all();
        $wishlistId = $attributes['wishlistId'];
        $anotherWishlist = $this->wishlistLoadRoute->getAnotherWishlist($request, $context, $customer, $wishlistId);
        return $anotherWishlist;
    }


    /**
     * @throws CategoryNotFoundException
     * @throws CustomerNotLoggedInException
     * @throws InconsistentCriteriaIdsException
     * @throws MissingRequestParameterException
     */
    public function loadShare(Request $request, SalesChannelContext $context): WishlistPage
    {

        $criteria = $this->createCriteria($request);
        //$this->eventDispatcher->dispatch(new WishlistPageProductCriteriaEvent($criteria, $context, $request));

        $page = $this->genericLoader->load($request, $context);
        $page = WishlistPage::createFrom($page);

        //$wishlistId = $request->wishlistid;
        $attributes = $request->attributes->all();
        $wishlistId = $attributes['wishlistId'];

        try {
            $page->setWishlist($this->wishlistLoadRoute->load($request, $context, $criteria, null, $wishlistId));

        } catch (CustomerWishlistNotFoundException $exception) {
            $page->setWishlist(
                new LoadWishlistRouteResponse(
                    new CustomerWishlistEntity(),
                    new EntitySearchResult(
                        'wishlist',
                        0,
                        new ProductCollection(),
                        null,
                        $criteria,
                        $context->getContext()
                    )
                )
            );
        }

        // $this->eventDispatcher->dispatch(
        //     new WishlistPageLoadedEvent($page, $context, $request)
        // );

        return $page;
    }
}
