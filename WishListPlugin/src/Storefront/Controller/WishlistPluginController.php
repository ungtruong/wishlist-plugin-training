<?php declare(strict_types=1);

namespace Wishlist\WishlistPlugin\Storefront\Controller;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Routing\Annotation\LoginRequired;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Storefront\Page\Suggest\SuggestPageLoader;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Config\Shopware\SalesChannelContextConfig;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;

/**
 * @RouteScope(scopes={"storefront"})
 */
class WishlistPluginController extends StorefrontController
{

    CONST MAX_WISHLIST_CONFIG = 'WishlistPlugin.config.maxWhishList';
    CONST DELETE_PRODUCT_AFTER_ADD_CARD = "WishlistPlugin.config.isRemove";
    private $wishlistRepository;
    private $wishlistProductRepository;
    private $productRepository;
    /**
     * @var SuggestPageLoader
     */
    private $suggestPageLoader;
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    private $productSalesRepository;

    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $wishlistRepository,
        EntityRepositoryInterface $wishlistProductRepository,
        SuggestPageLoader $suggestPageLoader,
        // SalesChannelRepositoryInterface $productRepository
        EntityRepositoryInterface $productRepository
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->wishlistRepository = $wishlistRepository;
        $this->wishlistProductRepository = $wishlistProductRepository;
        $this->suggestPageLoader = $suggestPageLoader;
        $this->productRepository = $productRepository;

    }

    
    /**
     * @LoginRequired()
     * @Route("account/wishlist-plugin", name="frontend.wishlistplugin.page", methods={"GET"})
     * @throws CustomerNotLoggedInException
     */
    public function list(Context $context, CustomerEntity $customer): Response
    {
        $max = $this->systemConfigService->get(self::MAX_WISHLIST_CONFIG);

        $customerId = $customer->getId();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        $criteria->addSorting(new FieldSorting('createdAt')); 
        $criteria->addAssociation('wishlistProducts');

        $wishlistData = $this->wishlistRepository->search($criteria, $context)->getElements();

        return $this->renderStorefront('@WishlistPlugin/storefront/page/wishlist-plugin/index.html.twig', [
            'wishlistData'=> $wishlistData,
        ]);

    }

    /**
     * 
     * @Route("account/wishlist-plugin-create", name="frontend.wishlistplugin.create", methods={"POST"})
     * @LoginRequired
     * @throws CustomerNotLoggedInException
     */
    public function create(Request $request, SalesChannelContext $context, CustomerEntity $customer): Response
    {
        $maxWishlist = $this->systemConfigService->get(self::MAX_WISHLIST_CONFIG);
        $customer = $context->getCustomer();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customer->id));
        $whishLists = $this->wishlistRepository->search($criteria, $context->getContext())->getElements();
        if ($maxWishlist > count($whishLists)) {
            $request = $request->request->all();
            try {
                $result = $this->wishlistRepository->create([
                                [
                                    'name' => $request['name'],
                                    'customerId' => $customer->getId(),
                                ]
                          ], $context->getContext());
                if (!$result->getErrors()) {
                    $this->addFlash(self::SUCCESS, 'Create new wishlist successfully!');
                } else {
                    $this->addFlash(self::DANGER, 'You have reached maximum wishlist.');
                }
            } catch (\Exception $exception) { 
                $this->addFlash(self::DANGER, 'You can not create new wishlist at the moment.');
            }
        }  else {
            $this->addFlash(self::DANGER, 'You have reached maximum wishlist.');
        }

        return new RedirectResponse($this->generateUrl('frontend.wishlistplugin.page'));

    }

    /**
     * 
     * @Route("account/wishlist-plugin/detail/{wishlistId}", name="frontend.wishlistplugin.detail", methods={"GET"})
     * 
     */
    /*public function detail(SalesChannelContext $context, String $wishlistId): Response
    {

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $wishlistId));
        $criteria->addAssociation('wishlistProducts.products.media');

        $productWishlistData = $this->wishlistRepository->search($criteria, $context->getContext())->first();
        
        // c696c6f17b7f47c9995a38710707099f
        // $criteria = new Criteria();
        // $criteria->addFilter(new EqualsFilter('id', 'c696c6f17b7f47c9995a38710707099f'));
        // $criteria->addAssociation('wishlistProducts');
        // $criteria->addAssociation('wishlistPlugins');
        // //$criteria->addFilter(new EqualsFilter('productNumber', '13d4fd80f3844923873848543ee7bd09'));
        // $a = $this->productRepository->search($criteria, $context->getContext())->first();
        // dd($a->getExtensions());
        // exit;
        
        if ($productWishlistData) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('customerId', $productWishlistData->customerId));
            $criteria->addSorting(new FieldSorting('createdAt')); 
            $wishlistData = $this->wishlistRepository->search($criteria, $context->getContext())->getElements();
        }

        

        return $this->renderStorefront('@WishlistPlugin/storefront/page/wishlist-plugin/detail.html.twig', [
            'productWishlistData' => $productWishlistData,
            'wishlistData' => $wishlistData,
        ]);

    }*/

    /**
     * 
     * @Route("account/wishlist-plugin-update-quantity", name="frontend.wishlistplugin.update.quantity", methods={"POST"})
     * @LoginRequired
     * @throws CustomerNotLoggedInException
     */
    public function updateQuantity(Request $request, Context $context, CustomerEntity $customer): Response
    {
        $request = $request = $request->request->all();
        try {

            if ($request['quantity'] === null) {
                throw new \InvalidArgumentException('quantity field is required');
            }
            $result = $this->wishlistProductRepository->update([
                [
                    'id' => $request['id'],
                    'quantity' => (int) $request['quantity'],
    
                ]
            ], $context);

            if (!$result->getErrors()) {
                $this->addFlash(self::SUCCESS, 'Update Wishlist Successfully!');
            }
        } catch (\Exception $exception) {
            $this->addFlash(self::DANGER, 'Update Wishlist Fail!');
        }

        return new RedirectResponse($request['redirectTo']);

    }

    /**
     * 
     * @Route("account/wishlist-plugin-delete-product/{wishlistProductId}", name="frontend.wishlistplugin.delete.product", methods={"POST"})
     * @LoginRequired
     * @throws CustomerNotLoggedInException
     */
    public function deleteProduct(Request $request, Context $context, CustomerEntity $customer, String $wishlistProductId): Response
    {
        $request = $request = $request->request->all();
        try {

            $result = $this->wishlistProductRepository->delete([
                [
                    'id' => $wishlistProductId,

                ]
            ], $context);

            if (!$result->getErrors()) {
                $this->addFlash(self::SUCCESS, 'Update Wishlist Successfully!');
            }

        } catch (\Exception $exception) {
            $this->addFlash(self::DANGER, 'Delete Product Fail!');
        }

        return new RedirectResponse($request['redirectTo']);

    }

    /**
     * 
     * @Route("account/wishlist-plugin-change-name/{wishlistId}", name="frontend.wishlistplugin.update.name", methods={"POST"})
     * @LoginRequired
     * @throws CustomerNotLoggedInException
     */
    public function updateName(Request $request, Context $context, CustomerEntity $customer, String $wishlistId): Response
    {
        $request = $request->request->all();
        try {

            $result = $this->wishlistRepository->update([
                [
                    'id' => $wishlistId,
                    'name' => $request['name']

                ]
            ], $context);

            if (!$result->getErrors()) {
                $this->addFlash(self::SUCCESS, 'Update Wishlist Name Successfully!');
            }

        } catch (\Exception $exception) {
             $this->addFlash(self::DANGER, 'Update Product Name Fail!');
        }

        return new RedirectResponse($this->generateUrl('frontend.wishlistplugin.detail', ['wishlistId' => $wishlistId]));

    }

    /**
     * @Route("account/wishlist-plugin/suggest", name="frontend.wishlistplugin.search.suggest", methods={"GET"}, defaults={"XmlHttpRequest"=true})
     */
    public function suggest(SalesChannelContext $context, Request $request): Response
    {
        $page = $this->suggestPageLoader->load($request, $context);
        return $this->renderStorefront('@Storefront/storefront/page/wishlist-plugin/search/search-suggest.html.twig', ['page' => $page]);
    }


    /**
     * 
     * @Route("account/wishlist-plugin/add-product-from-suggest/{wishlistId}", name="frontend.wishlistplugin.add.productSuggest", methods={"POST"}, defaults={"XmlHttpRequest"=true})
     */
    public function addProductSuggest(Context $context, Request $request, String $wishlistId): Response
    {
        
        $request = $request->request->all();
        $productIds = ($request['products']) ?? [];
        $dataInsert = [];
        foreach ($productIds as $productId) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('productId', $productId));
            $criteria->addFilter(new EqualsFilter('wishlistPluginId', $wishlistId));
            $wishlistData = $this->wishlistProductRepository->search($criteria, $context)->getElements();
            if (!$wishlistData) {
                $dataInsert[] = [
                    'wishlistPluginId' => $wishlistId,
                    'productId' => $productId,
                    'quantity' => 1,
                ];
            }
        }
        if ($dataInsert) {
            try {
                $result = $this->wishlistProductRepository->create($dataInsert, $context);
                if (!$result->getErrors()) {

                    $this->addFlash(self::SUCCESS, 'Add '. count($dataInsert) . '  product(s) Successfully!');
                }
            } catch (\Exception $exception) {
                $this->addFlash(self::DANGER, 'Add Product Fail!');
           }
        }
        
        return new RedirectResponse($this->generateUrl('frontend.wishlistplugin.detail', ['wishlistId' => $wishlistId]));
    }


    /**
     *  
     * @Route("account/wishlist-plugin/delete/{wishlistId}", name="frontend.wishlistplugin.delete", methods={"POST"}, defaults={"XmlHttpRequest"=true})
     */
    public function deleteWishlist(SalesChannelContext $context, Request $request, String $wishlistId): Response
    {
        $customer = $context->getCustomer();
        try {
            if ($customer ) {
                $criteria = new Criteria();
                $criteria->addFilter(new EqualsFilter('id', $wishlistId));
                $criteria->addFilter(new EqualsFilter('customerId', $customer->id));
                $criteria->addAssociation('wishlistProducts');
                $wishlistData = $this->wishlistRepository->search($criteria, $context->getContext())->first();
                
                if ($wishlistData) {
                    $wishlistPluginIds = $wishlistData->wishlistProducts->getIds();
                    $dataDelete = [];
                    foreach ($wishlistPluginIds as $wishlistPluginId) {
                        $dataDelete[] = ['id' => $wishlistPluginId];
                    }
    
                    if ($dataDelete) {
                        $result = $this->wishlistProductRepository->delete($dataDelete,$context->getContext());
                    }
                    $result = $this->wishlistRepository->delete([
                        [
                            'id' => $wishlistId
                        ]
                    ],  $context->getContext());
                    $this->addFlash(self::SUCCESS, 'Delete Wishlist Successfully!');
                }
                
            
            }
        } catch (\Exception $exceptio) {
            $this->addFlash(self::DANGER, 'Delete Wishlist Fail');
        }

        return new RedirectResponse($this->generateUrl('frontend.wishlistplugin.page'));
    }
}