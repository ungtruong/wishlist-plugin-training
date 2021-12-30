<?php declare(strict_types=1);

namespace Wishlist\WishlistPlugin\Core\Checkout\Customer\SalesChannel;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use OpenApi\Annotations as OA;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Event\WishlistMergedEvent;
use Shopware\Core\Checkout\Customer\Exception\CustomerWishlistNotActivatedException;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\FetchModeHelper;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\LoginRequired;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SuccessResponse;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

/**
 * @RouteScope(scopes={"store-api"})
 */
class MergeWishlistProductRoute extends AbstractMergeWishlistProductRoute
{
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
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(
        EntityRepositoryInterface $wishlistRepository,
        SalesChannelRepositoryInterface $productRepository,
        EntityRepositoryInterface $wishlistProductRepository,
        SystemConfigService $systemConfigService,
        EventDispatcherInterface $eventDispatcher,
        Connection $connection
    ) {
        $this->wishlistRepository = $wishlistRepository;
        $this->productRepository = $productRepository;
        $this->wishlistProductRepository = $wishlistProductRepository;
        $this->systemConfigService = $systemConfigService;
        $this->eventDispatcher = $eventDispatcher;
        $this->connection = $connection;
    }

    public function getDecorated(): AbstractMergeWishlistProductRoute
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @Since("6.3.4.0")
     * @OA\Post(
     *      path="/customer/wishlist/merge",
     *      summary="Create a wishlist for a customer",
     *      description="Create a new wishlist for a logged in customer or extend the existing wishlist given a set of products.

**Important constraints**

* Anonymous (not logged-in) customers can not have wishlists.
* A customer can only have a single wishlist.
* The wishlist feature has to be activated.",
     *      operationId="mergeProductOnWishlist",
     *      tags={"Store API", "Wishlist"},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="productIds",
     *                  description="List product id",
     *                  type="array",
     *                  @OA\Items(type="string", pattern="^[0-9a-f]{32}$", description="product id")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="Returns a success response.",
     *          @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     * @LoginRequired()
     * @Route("/store-api/customer/wishlist/merge", name="store-api.customer.wishlist.merge", methods={"POST"})
     */
    public function merge(RequestDataBag $data, SalesChannelContext $context, CustomerEntity $customer): SuccessResponse
    {
        // if (!$this->systemConfigService->get('core.cart.wishlistEnabled', $context->getSalesChannel()->getId())) {
        //     throw new CustomerWishlistNotActivatedException();
        // }

        $wishlistId = $this->getWishlistId($context, $customer->getId());
        if ($wishlistId) {
            $this->wishlistRepository->update([
                [
                    'customerId' => $customer->getId(),
                    'id' => $wishlistId
                ]
            ], $context->getContext());
        } else {
            $wishlistId = Uuid::randomHex();
            $this->wishlistRepository->create([
                [
                    'name' => "My Wishlist",
                    'customerId' => $customer->getId(),
                    'id' => $wishlistId
                ]
            ], $context->getContext());
        }

        $upsertData = $this->buildUpsertProducts($data, $wishlistId, $context);
        $this->wishlistRepository->upsert([[
            'id' => $wishlistId,
            'customerId' => $customer->getId(),
            //'salesChannelId' => $context->getSalesChannel()->getId(),
            'wishlistProducts' => $upsertData,
        ]], $context->getContext());

        // $this->eventDispatcher->dispatch(new WishlistMergedEvent($upsertData, $context));

        return new SuccessResponse();
    }

    private function getWishlistId(SalesChannelContext $context, string $customerId): string
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
            new EqualsFilter('customerId', $customerId),
            // new EqualsFilter('salesChannelId', $context->getSalesChannel()->getId()),
        ]));
        $criteria->addSorting(new FieldSorting('createdAt')); 
        $wishlistIds = $this->wishlistRepository->searchIds($criteria, $context->getContext());

        return $wishlistIds->firstId() ?? "";
    }

    private function buildUpsertProducts(RequestDataBag $data, string $wishlistId, SalesChannelContext $context): array
    {
        $ids = array_unique(array_filter($data->get('productIds')->all()));

        $ids = $this->productRepository->searchIds(new Criteria($ids), $context)->getIds();

        $customerProducts = $this->loadCustomerProducts($wishlistId, $ids);
        
        $upsertData = [];

        /** @var string $id * */
        foreach ($ids as $id) {
            if (\array_key_exists($id, $customerProducts)) {
                $upsertData[] = [
                    'id' => $customerProducts[$id],
                ];

                continue;
            }

            $upsertData[] = array_filter([
                'id' => Uuid::randomHex(),
                'productId' => $id,
                'wishlistPluginId' => $wishlistId,
                'quantity' => 1
                //'productVersionId' => Defaults::LIVE_VERSION,
            ]);
        }

        return $upsertData;
    }

    private function loadCustomerProducts(string $wishlistId, array $productIds): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select([
            'LOWER(HEX(`product_id`)) as `product_id`',
            'LOWER(HEX(`id`)) as id',
        ]);
        $query->from('`wishlist_plugin_product`');
        $query->where('`wishlist_plugin_id` = :id');
        $query->andWhere('`product_id` IN (:productIds)');
        $query->setParameter('id', Uuid::fromHexToBytes($wishlistId));
        $query->setParameter('productIds', Uuid::fromHexToBytesList($productIds), Connection::PARAM_STR_ARRAY);
        /** @var Statement $stmt */
        $stmt = $query->execute();

        return FetchModeHelper::keyPair($stmt->fetchAll());
    }
}
