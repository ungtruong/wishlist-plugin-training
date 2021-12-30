<?php declare(strict_types=1);

namespace Wishlist\WishlistPlugin\Core\Checkout\Customer\SalesChannel;

use OpenApi\Annotations as OA;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\FetchModeHelper;
use phpDocumentor\Reflection\Types\Boolean;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerWishlist\CustomerWishlistEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Event\CustomerWishlistLoaderCriteriaEvent;
use Shopware\Core\Checkout\Customer\Event\CustomerWishlistProductListingResultEvent;
use Shopware\Core\Checkout\Customer\Exception\CustomerWishlistNotActivatedException;
use Shopware\Core\Checkout\Customer\Exception\CustomerWishlistNotFoundException;
use Shopware\Core\Content\Product\SalesChannel\ProductCloseoutFilter;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\Entity;
use Shopware\Core\Framework\Routing\Annotation\LoginRequired;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Wishlist\WishlistPlugin\Core\Content\WishlistPlugin\WishlistPluginEntityCollection;
use Wishlist\WishlistPlugin\Extension\Content\Product\WishlistPluginProductEntityCollection;

/**
 * @RouteScope(scopes={"store-api"})
 */
class LoadWishlistRoute extends AbstractLoadWishlistRoute
{
    const MAX_WISHLIST_ITEM_PER_PAGE_CONFIG = 'WishlistPlugin.config.itemPerPage';
    const MAX_WISHLIST_CONFIG = 'WishlistPlugin.config.maxWhishList';
    const COUNT_POPULAR_PRODUCT_CONFIG = 'WishlistPlugin.config.popularProduct';
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var EntityRepositoryInterface
     */
    private $wishlistRepository;

    /**
     * @var SalesChannelRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var EntityRepositoryInterface
     */
    private $wishlistProductRepository;

    public function __construct(
        EntityRepositoryInterface $wishlistRepository,
        SalesChannelRepositoryInterface $productRepository,
        EventDispatcherInterface $eventDispatcher,
        SystemConfigService $systemConfigService,
        Connection $connection,
        EntityRepositoryInterface $wishlistProductRepository

    ) {
        $this->wishlistRepository = $wishlistRepository;
        $this->productRepository = $productRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->systemConfigService = $systemConfigService;
        $this->connection = $connection;
        $this->wishlistProductRepository = $wishlistProductRepository;
        $this->wishlistProductRepository = $wishlistProductRepository;
    }

    public function getDecorated(): AbstractLoadWishlistRoute
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @Since("6.3.4.0")
     * @Entity("product")
     * @OA\Post(
     *      path="/customer/wishlist",
     *      summary="Fetch a wishlist",
     *      description="Fetch a customer's wishlist. Products on the wishlist can be filtered using a criteria object.

**Important constraints**

* Anonymous (not logged-in) customers can not have wishlists.
* The wishlist feature has to be activated.",
     *      operationId="readCustomerWishlist",
     *      tags={"Store API", "Wishlist"},
     *      @OA\Parameter(name="Api-Basic-Parameters"),
     *      @OA\Response(
     *          response="200",
     *          description="",
     *          @OA\JsonContent(ref="#/components/schemas/WishlistLoadRouteResponse")
     *     )
     * )
     * @LoginRequired()
     * @Route("/store-api/customer/wishlist", name="store-api.customer.wishlist.load", methods={"GET", "POST"})
     */
    public function load(Request $request, SalesChannelContext $context, Criteria $criteria, ?CustomerEntity $customer, String $wishlistId): LoadWishlistRouteResponse
    {
        // if (!$this->systemConfigService->get('core.cart.wishlistEnabled', $context->getSalesChannel()->getId())) {
        //     throw new CustomerWishlistNotActivatedException();
        // }
        $customerId = ($customer) ?  $customer->getId() : null;
        $wishlist = $this->loadWishlist($context, $customerId, $wishlistId);
        $products = $this->loadProducts($wishlist->getId(), $criteria, $context, $request);
        
        return new LoadWishlistRouteResponse($wishlist, $products);
    }

    //private function loadWishlist(SalesChannelContext $context, string $customerId): CustomerWishlistEntity
    private function loadWishlist(SalesChannelContext $context, ?string $customerId,  String $wishlistId)
    {
        $criteria = new Criteria();
        //$criteria->setLimit(2);


        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
            //new EqualsFilter('customerId', $customerId),
            new EqualsFilter('id', $wishlistId)
            //new EqualsFilter('salesChannelId', $context->getSalesChannel()->getId()),
        ]));


        $wishlist = $this->wishlistRepository->search($criteria, $context->getContext());
        if ($wishlist->first() === null) {
            throw new CustomerWishlistNotFoundException();
        }
        
        return $wishlist->first();
    }

    private function loadProducts(string $wishlistId, Criteria $criteria, SalesChannelContext $context, Request $request): EntitySearchResult
    {
        $criteria->addFilter(
            new EqualsFilter('wishlistPlugins.id', $wishlistId)
        );
        $criteria->addAssociation('wishlistProducts');
        // $criteria->addSorting(
        //     new FieldSorting('wishlists.updatedAt', FieldSorting::DESCENDING)
        // );

        // $criteria->addSorting(
        //     new FieldSorting('wishlists.createdAt', FieldSorting::DESCENDING)
        // );

        $criteria = $this->handleAvailableStock($criteria, $context);

        $event = new CustomerWishlistLoaderCriteriaEvent($criteria, $context);
        $this->eventDispatcher->dispatch($event);

        $products = $this->productRepository->search($criteria, $context);
        $event = new CustomerWishlistProductListingResultEvent($request, $products, $context);
        $this->eventDispatcher->dispatch($event);

        return $products;
    }

    private function handleAvailableStock(Criteria $criteria, SalesChannelContext $context): Criteria
    {
        $hide = $this->systemConfigService->getBool(
            'core.listing.hideCloseoutProductsWhenOutOfStock',
            $context->getSalesChannelId()
        );

        if (!$hide) {
            return $criteria;
        }

        $criteria->addFilter(new ProductCloseoutFilter());

        return $criteria;
    }

    public function getAnotherWishlist(Request $request, SalesChannelContext $context,  CustomerEntity $customer, String $wishlistId) : WishlistPluginEntityCollection
    {

        $criteria = new Criteria();
        $wishlist = $this->loadWishlist($context, $customer->getId(), $wishlistId);
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
            new EqualsFilter('customerId',$customer->getId())
        ]));
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [
            new EqualsFilter('id', $wishlistId)
        ]));
        $wishlist = $this->wishlistRepository->search($criteria, $context->getContext());
        
        return $wishlist->getEntities();
    }


    public function getItemPerPage() : int
    {
        return  $this->systemConfigService->get(self::MAX_WISHLIST_ITEM_PER_PAGE_CONFIG);
    }

    public function getAllProduct(SalesChannelContext $context, String $wishlistId) {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('wishlistPlugins.id', $wishlistId)
        );
        $criteria->addAssociation('wishlistProducts');
        $products = $this->productRepository->search($criteria, $context);
        return $products;
    }

    public function getAllProductByCustomer(Request $request, SalesChannelContext $context, String $customerId ): Array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
            new EqualsFilter('customerId', $customerId),
            //new EqualsFilter('salesChannelId', $context->getSalesChannel()->getId()),
        ]));
        $criteria->addAssociation('wishlistProducts');
        $wishlists = $this->wishlistRepository->search($criteria, $context->getContext())->getEntities();
        $array = [];
        foreach ($wishlists as $wishlist) {
            //$array = array_merge($array, $wishlist->wishlistProducts->getElements());
            $tmp = array_map(function ($wishlist) {
                    return $wishlist->productId;
                }, $wishlist->wishlistProducts->getElements());  
            $array = array_merge($array, $tmp);          
        }

        return $array;
    }


    public function getAllWishlistToAdd(SalesChannelContext $context,  CustomerEntity $customer, String $productId) 
    {
        $criteria = new Criteria();
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
            new EqualsFilter('customerId', $customer->getId()),
            //new EqualsFilter('salesChannelId', $context->getSalesChannel()->getId()),
        ]));
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [
            new EqualsFilter('wishlistProducts.productId', $productId)
        ]));


        $wishlists = $this->wishlistRepository->search($criteria, $context->getContext())->getEntities();
        return $wishlists;
    }

    public function isMaxWishlist(SalesChannelContext $context, CustomerEntity $customer) : bool
    {
       $wishlists= $this->getAllWishlist($context, $customer);
       return (count($wishlists) >= self::MAX_WISHLIST_CONFIG) ? false : true;
    }
    

    public function getWishlistOfProduct(SalesChannelContext $context, CustomerEntity $customer, String $productId)
    {
        $criteria = new Criteria();


        $criteria->addAssociation('wishlistPlugin');
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
            new EqualsFilter('wishlistPlugin.customerId', $customer->getId()),
            //new EqualsFilter('salesChannelId', $context->getSalesChannel()->getId()),
        ]));
        $criteria->addFilter(new EqualsFilter('productId', $productId));
        $wishlistsProducts = $this->wishlistProductRepository->search($criteria, $context->getContext())->getEntities();
        return $wishlistsProducts;
    }


    public function getPopularProduct(SalesChannelContext $context) 
    {

        $ids = $this->getPopularProductId($this->systemConfigService->get(self::COUNT_POPULAR_PRODUCT_CONFIG));
        if ($ids) {
            $criteria = new Criteria($ids);
            $criteria->setLimit(10);
            $products = $this->productRepository->search($criteria, $context);
            return $products;
        }
        return null;
    }

    private function getPopularProductId(int $count_popular) {

        $query = $this->connection->createQueryBuilder();
        $query->select([
            'LOWER(HEX(`product_id`)) as `product_id`'
        ]);
        $query->from('`wishlist_plugin_product`');
        $query->groupBy('`product_id`');
        $query->having('COUNT(`product_id`)>=:count_popular');
        $query->setParameter('count_popular', $count_popular);
        
        /** @var Statement $stmt */
        $stmt = $query->execute();
        $result = $stmt->fetchAll();
        $ids = [];
        $ids = array_map(function($v) {
            return $v['product_id'];
        }, $result);
        return $ids;
    }
}
