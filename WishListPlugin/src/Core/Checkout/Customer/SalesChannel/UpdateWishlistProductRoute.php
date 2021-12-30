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

/**
 * @RouteScope(scopes={"store-api"})
 */
class UpdateWishlistProductRoute  extends AbstractUpdateWishlistProductRoute
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
        EntityRepositoryInterface $productRepository,
        SystemConfigService $systemConfigService,
        EventDispatcherInterface $eventDispatcher,
        Connection $connection
    ) {
        $this->wishlistRepository = $wishlistRepository;
        $this->productRepository = $productRepository;
        $this->systemConfigService = $systemConfigService;
        $this->eventDispatcher = $eventDispatcher;
        $this->connection = $connection;
    }

    public function getDecorated(): AbstractMergeWishlistProductRoute
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @LoginRequired()
     * @Route("/store-api/customer/wishlist/update-quantity", name="store-api.customer.wishlist.update.quantity", methods={"POST"})
     */
    public function updateQuantity(String $id, int $quantity, SalesChannelContext $context, CustomerEntity $customer): SuccessResponse
    {
        $this->productRepository->update([
            [
                'id' => $id,
                'quantity' => $quantity,
            ]
        ], $context->getContext());

        return new SuccessResponse();
    }

    public function addProduct(String $wishlistId, String $productId, SalesChannelContext $context, CustomerEntity $customer): SuccessResponse
    {
        $criteria = new Criteria();
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
            new EqualsFilter('productId', $wishlistId),
            new EqualsFilter('wishlistPluginId', $wishlistId),
            //new EqualsFilter('salesChannelId', $context->getSalesChannel()->getId()),
        ]));

        $wishlistProduct = $this->productRepository->search($criteria, $context->getContext());
        // if ($wishlistProduct->first() === null) {
        //     throw new CustomerWishlistNotFoundException();
        // }

        $dataInsert[] = [
            'wishlistPluginId' => $wishlistId,
            'productId' => $productId,
            'quantity' => 1,
        ];
        $this->productRepository->create($dataInsert,  $context->getContext());
        return new SuccessResponse();
    }
}
