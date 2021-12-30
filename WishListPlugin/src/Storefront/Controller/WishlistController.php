<?php declare(strict_types=1);

namespace Wishlist\WishlistPlugin\Storefront\Controller;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\CustomerWishlistNotFoundException;
use Shopware\Core\Checkout\Customer\Exception\DuplicateWishlistProductException;

use Wishlist\WishlistPlugin\Core\Checkout\Customer\SalesChannel\AbstractAddWishlistProductRoute;
use Wishlist\WishlistPlugin\Core\Checkout\Customer\SalesChannel\AbstractLoadWishlistRoute;
use Wishlist\WishlistPlugin\Core\Checkout\Customer\SalesChannel\AbstractMergeWishlistProductRoute;
use Wishlist\WishlistPlugin\Core\Checkout\Customer\SalesChannel\AbstractRemoveWishlistProductRoute;
use Wishlist\WishlistPlugin\Core\Checkout\Customer\SalesChannel\AbstractUpdateWishlistProductRoute;
use Wishlist\WishlistPlugin\Core\Checkout\Customer\SalesChannel\AbstractWishlistHandleRoute;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\LoginRequired;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

use Wishlist\WishlistPlugin\Storefront\Page\Wishlist\GuestWishlistPageLoader;
use Wishlist\WishlistPlugin\Storefront\Page\Wishlist\WishlistPageLoader;
use Shopware\Storefront\Page\Wishlist\WishlistPageProductCriteriaEvent;

use Wishlist\WishlistPlugin\Storefront\Pagelet\Wishlist\GuestWishlistPageletLoader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Serializer\Normalizer\CustomNormalizer;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @RouteScope(scopes={"storefront"})
 */
class WishlistController extends StorefrontController
{
    CONST DELETE_PRODUCT_AFTER_ADD_CARD = "WishlistPlugin.config.isRemove";

    private WishlistPageLoader $wishlistPageLoader;

    private AbstractLoadWishlistRoute $wishlistLoadRoute;

    private AbstractAddWishlistProductRoute $addWishlistRoute;

    private AbstractRemoveWishlistProductRoute $removeWishlistProductRoute;

    private AbstractMergeWishlistProductRoute $mergeWishlistProductRoute;

    private GuestWishlistPageLoader $guestPageLoader;

    private GuestWishlistPageletLoader $guestPageletLoader;

    private EventDispatcherInterface $eventDispatcher;

    private AbstractUpdateWishlistProductRoute $updateWishlistProductRoute;
    /**
     * @var CartService
     */
    private $cartService;
    private $suggestPageLoader;
    /**
     * @var SystemConfigService
     */

    private AbstractWishlistHandleRoute $wishlistHandleRoute;

    public function __construct(
        WishlistPageLoader $wishlistPageLoader,
        AbstractLoadWishlistRoute $wishlistLoadRoute,
        AbstractAddWishlistProductRoute $addWishlistRoute,
        AbstractRemoveWishlistProductRoute $removeWishlistProductRoute,
        AbstractMergeWishlistProductRoute $mergeWishlistProductRoute,
        GuestWishlistPageLoader $guestPageLoader,
        GuestWishlistPageletLoader $guestPageletLoader,
        EventDispatcherInterface $eventDispatcher,
        AbstractUpdateWishlistProductRoute $updateWishlistProductRoute,
        CartService $cartService,
        AbstractWishlistHandleRoute $wishlistHandleRoute,
        SystemConfigService $systemConfigService
    ) {
        $this->wishlistPageLoader = $wishlistPageLoader;
        $this->wishlistLoadRoute = $wishlistLoadRoute;
        $this->addWishlistRoute = $addWishlistRoute;
        $this->removeWishlistProductRoute = $removeWishlistProductRoute;
        $this->mergeWishlistProductRoute = $mergeWishlistProductRoute;
        $this->guestPageLoader = $guestPageLoader;
        $this->guestPageletLoader = $guestPageletLoader;
        $this->eventDispatcher = $eventDispatcher;
        $this->updateWishlistProductRoute = $updateWishlistProductRoute;
        $this->cartService = $cartService;
        $this->wishlistHandleRoute = $wishlistHandleRoute;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * 
     * @Route("account/wishlist-plugin/detail/{wishlistId}", name="frontend.wishlistplugin.detail", methods={"GET"})
     */
    public function detailIndex(Request $request, SalesChannelContext $context, String $wishlistId): Response
    {
        $customer = $context->getCustomer();
        $anotherWishlist = [];
        if ($customer !== null && $customer->getGuest() === false) {
            $page = $this->wishlistPageLoader->load($request, $context, $customer);
            $anotherWishlist = $this->wishlistPageLoader->loadAnotherWishlist($request, $context, $customer);
        } else {
            $page = $this->guestPageLoader->load($request, $context);
        }
        
        //return $this->renderStorefront('@Storefront/storefront/page/wishlist/index.html.twig', ['page' => $page]);

        return $this->renderStorefront('@WishlistPlugin/storefront/page/wishlist-plugin/detail.html.twig', ['page' => $page, 'anotherWishlist' => $anotherWishlist]);
    }

    /**
     * @Route("/wishlist/guest-pagelet", name="frontend.wishlist.guestPage.pagelet", options={"seo"="false"}, methods={"POST"}, defaults={"XmlHttpRequest"=true})
     */
    public function guestPagelet(Request $request, SalesChannelContext $context): Response
    {
        $customer = $context->getCustomer();

        if ($customer !== null && $customer->getGuest() === false) {
            throw new NotFoundHttpException();
        }

        $pagelet = $this->guestPageletLoader->load($request, $context);

        return $this->renderStorefront(
            '@Storefront/storefront/page/wishlist/wishlist-pagelet.html.twig',
            ['page' => $pagelet, 'searchResult' => $pagelet->getSearchResult()->getObject()]
        );
    }

    /**
     * @LoginRequired()
     * @Route("/widgets/wishlist/{wishlistId}", name="widgets.wishlistplugin.pagelet", options={"seo"="false"}, methods={"GET", "POST"}, defaults={"XmlHttpRequest"=true})
     */
    public function ajaxPagination(Request $request, SalesChannelContext $context, CustomerEntity $customer, String $wishlistId): Response
    {
        $request->request->set('no-aggregations', true);

        $page = $this->wishlistPageLoader->load($request, $context, $customer);

        $response = $this->renderStorefront('@Wishlistplugin/storefront/page/wishlist/detail.html.twig', ['page' => $page]);
        $response->headers->set('x-robots-tag', 'noindex');

        return $response;
    }

    /**
     * @LoginRequired()
     * @Route("/wishlist/list", name="frontend.wishlist.product.list", options={"seo"="false"}, methods={"GET"}, defaults={"XmlHttpRequest"=true})
     */
    public function ajaxList(Request $request, SalesChannelContext $context, CustomerEntity $customer): Response
    {
        $criteria = new Criteria();
        //$this->eventDispatcher->dispatch(new WishlistPageProductCriteriaEvent($criteria, $context, $request));

        try {
            $res = $this->wishlistLoadRoute->getAllProductByCustomer($request, $context, $customer->getId());
        } catch (CustomerWishlistNotFoundException $exception) {
            return new JsonResponse([]);
        }

        return new JsonResponse($res);
    }

    /**
     * @LoginRequired()
     * @Route("/wishlist/product/delete/{wishlistProductId}", name="frontend.wishlist.product.delete", methods={"POST", "DELETE"}, defaults={"XmlHttpRequest"=true})
     */
    public function remove(string $wishlistProductId, Request $request, SalesChannelContext $context, CustomerEntity $customer): Response
    {
        if (!$wishlistProductId) {
            throw new MissingRequestParameterException('Parameter id missing');
        }
        $request = $request = $request->request->all();
        
        
        try {
            $this->removeWishlistProductRoute->delete($wishlistProductId, $context, $customer);

            $this->addFlash(self::SUCCESS, $this->trans('wishlist.itemDeleteSuccess'));
        } catch (\Throwable $exception) {
            $this->addFlash(self::DANGER, $this->trans('error.message-default'));
        }

        
        return new RedirectResponse($request['redirectTo']);

        
    }

    /**
     * @LoginRequired()
     * @Route("/wishlist/add/{productId}", name="frontend.wishlist.product.add", options={"seo"="false"}, methods={"POST"}, defaults={"XmlHttpRequest"=true})
     */
    public function ajaxAdd(string $productId, SalesChannelContext $context, CustomerEntity $customer): JsonResponse
    {
        try {
            $this->addWishlistRoute->add($productId, $context, $customer);
            $success = true;
        } catch (\Throwable $exception) {
            $success = false;
        }

        return new JsonResponse([
            'success' => $success,
        ]);
    }

    /**
     * @LoginRequired()
     * @Route("/wishlist/remove/{productId}", name="frontend.wishlist.product.remove", options={"seo"="false"}, methods={"POST"}, defaults={"XmlHttpRequest"=true})
     */
    public function ajaxRemove(string $productId, SalesChannelContext $context, CustomerEntity $customer): JsonResponse
    {
        try {
            $this->removeWishlistProductRoute->delete($productId, $context, $customer);
            $success = true;
        } catch (\Throwable $exception) {
            $success = false;
        }

        return new JsonResponse([
            'success' => $success,
        ]);
    }

    /**
     * @LoginRequired()
     * @Route("/wishlist/add-after-login/{productId}", name="frontend.wishlist.add.after.login", options={"seo"="false"}, methods={"GET"})
     */
    public function addAfterLogin(string $productId, SalesChannelContext $context, CustomerEntity $customer): Response
    {
        try {
            $this->addWishlistRoute->add($productId, $context, $customer);

            $this->addFlash(self::SUCCESS, $this->trans('wishlist.itemAddedSuccess'));
        } catch (DuplicateWishlistProductException $exception) {
            $this->addFlash(self::WARNING, $exception->getMessage());
        } catch (\Throwable $exception) {
            $this->addFlash(self::DANGER, $this->trans('error.message-default'));
        }

        return $this->redirectToRoute('frontend.home.page');
    }

    /**
     * @LoginRequired()
     * @Route("/wishlist/merge", name="frontend.wishlist.product.merge", options={"seo"="false"}, methods={"POST"}, defaults={"XmlHttpRequest"=true})
     */
    public function ajaxMerge(RequestDataBag $requestDataBag, Request $request, SalesChannelContext $context, CustomerEntity $customer): Response
    {
        try {
            $this->mergeWishlistProductRoute->merge($requestDataBag, $context, $customer);

            return $this->renderStorefront('@Storefront/storefront/utilities/alert.html.twig', [
                'type' => 'info', 'content' => $this->trans('wishlist.wishlistMergeHint'),
            ]);
        } catch (\Throwable $exception) {
             $this->addFlash(self::DANGER, $this->trans('error.message-default'));
        }

        return $this->createActionResponse($request);
    }

    /**
     * @LoginRequired()
     * @Route("/wishlist/merge/pagelet", name="frontend.wishlist.product.merge.pagelet", methods={"GET", "POST"}, defaults={"XmlHttpRequest"=true})
     */
    public function ajaxPagelet(Request $request, SalesChannelContext $context, CustomerEntity $customer): Response
    {
        $request->request->set('no-aggregations', true);

        $page = $this->wishlistPageLoader->load($request, $context, $customer);

        return $this->renderStorefront('@Storefront/storefront/page/wishlist/wishlist-pagelet.html.twig', ['page' => $page]);
    }


    /**
     * @LoginRequired()
     * @Route("/wishlist/product/update-quantity/{wislistProductId}", name="frontend.wishlist.product.update.quantity", methods={"POST", "DELETE"}, defaults={"XmlHttpRequest"=true})
     */
    public function updateQuantity(string $wislistProductId, Request $request, SalesChannelContext $context, CustomerEntity $customer): Response
    {
        if (!$wislistProductId) {
            throw new MissingRequestParameterException('Parameter id missing');
        }
        $request = $request = $request->request->all();
        
        
        try {
            $quantity = empty($request['quantity']) ? 1 : (int) $request['quantity'];

            $this->updateWishlistProductRoute->updateQuantity($wislistProductId, $quantity, $context, $customer);
            $this->addFlash(self::SUCCESS, 'Update quantity!');
        } catch (\Throwable $exception) {
            $this->addFlash(self::DANGER, $this->trans('error.message-default'));
        }

        return new RedirectResponse($request['redirectTo']);
    }


    /**
     * @Route("wishlist-plugin/share/{wishlistId}", name="frontend.wishlistplugin.share", methods={"GET"})
     */
    public function detailShare(Request $request, SalesChannelContext $context, String $wishlistId): Response
    {
        $customer = $context->getCustomer();
        $page = $this->wishlistPageLoader->loadShare($request, $context, $customer);

        //return $this->renderStorefront('@Storefront/storefront/page/wishlist/index.html.twig', ['page' => $page]);
        return $this->renderStorefront('@WishlistPlugin/storefront/page/wishlist-plugin/detail.html.twig', ['page' => $page, 'shared' => true]);
    }


    /**
     * 
     * @LoginRequired()
     * @Route("account/wishlist-plugin/product/delete-all/{wishlistId}", name="frontend.wishlistplugin.product.delete.all", methods={"POST"})
     */
    public function deleteAllProducs(Request $request, SalesChannelContext $context,CustomerEntity $customer,String $wishlistId): Response
    {
        $request = $request = $request->request->all();
        try {
            $customer = $context->getCustomer();
            $this->removeWishlistProductRoute->deleteAll($wishlistId, $context, $customer);
            $this->addFlash(self::SUCCESS, $this->trans('wishlist.itemDeleteSuccess'));
        } catch (\Throwable $exception) {
            $this->addFlash(self::DANGER, $this->trans('error.message-default'));
        }
        return new RedirectResponse($request['redirectTo']);
    }

    /**
     * @Since("6.3.4.0")
     * @LoginRequired()
     * @Route("account/wishlist-plugin/product/add-card-all/{wishlistId}", name="frontend.wishlistplugin.product.add.card.all", methods={"POST"}, defaults={"XmlHttpRequest"=true}) 
     */
    public function addAllProduct(Cart $cart, Request $request, SalesChannelContext $context,CustomerEntity $customer,String $wishlistId): Response
    {
        try {
            $lineItems = $this->wishlistLoadRoute->getAllProduct($context, $wishlistId);
            $count = 0;
            $items = [];
            /** @var RequestDataBag $lineItemData */
            foreach ($lineItems as $lineItemData) {
                $productExtension = $lineItemData->getExtensions();
                
                $wishlistProduct = empty($productExtension['wishlistProducts']) ? [] : $productExtension['wishlistProducts']->first();
                
                $id = $lineItemData->id;
                $type = "product";
                $referencedId = $lineItemData->id;
                $quantity = ($wishlistProduct) ? $wishlistProduct->quantity : 1;
                $lineItem = new LineItem(
                    $id,
                    $type,
                    $referencedId,
                    $quantity
                );

                $lineItem->setStackable(true);
                $lineItem->setRemovable(true);

                $count += $lineItem->getQuantity();

                $items[] = $lineItem;
            }

            $cart = $this->cartService->add($cart, $items, $context);
            if ($this->systemConfigService->get(self::DELETE_PRODUCT_AFTER_ADD_CARD)) {
                $this->removeWishlistProductRoute->deleteAll($wishlistId, $context, $customer);
            }
            
            if (!$this->traceErrors($cart)) {
                $this->addFlash(self::SUCCESS, $this->trans('checkout.addToCartSuccess', ['%count%' => $count]));
            }
        } catch (\Throwable $exception) {
            
        }
        return $this->createActionResponse($request);
    }
    

    private function traceErrors(Cart $cart): bool
    {
        if ($cart->getErrors()->count() <= 0) {
            return false;
        }

        $this->addCartErrors($cart, function (Error $error) {
            return $error->isPersistent();
        });

        return true;
    }


    /**
     * 
     * @LoginRequired()
     * @Route("wishlist/get/all-wishlist-add-product/{productId}", name="frontend.wishlist.get.wishlist.add.product", methods={"GET", "POST"}, defaults={"XmlHttpRequest"=true})
     */
    public function ajaxGetWishlistToAdd(Request $request, SalesChannelContext $context, CustomerEntity $customer, String $productId): Response
    {
        $request->request->set('no-aggregations', true);
        try {
            $wishlists = $this->wishlistLoadRoute->getAllWishlistToAdd($context, $customer, $productId);
            if ($wishlists) {
                $res = array_map(function ($wishlist) {
                    return $wishlist->name;
                }, $wishlists->getElements());
                return new JsonResponse($res);
            }
        } catch (\Throwable $exception) {
            $success = false;
        }

        return new JsonResponse([]);
    }

    /**
     * 
     * @LoginRequired()
     * @Route("wishlist/upsert/product", name="frontend.wishlist.upsert.product", methods={"POST"}, defaults={"XmlHttpRequest"=true})
     */
    public function upsertProductToWishlist(Request $request, SalesChannelContext $context, CustomerEntity $customer): Response
    {
        $data = $request->request->all();
        $success = false;
        try {
            if ($data['wishlistId']) {
               
                $this->updateWishlistProductRoute->addProduct($data['wishlistId'], $data['productId'], $context, $customer);
                $success = true;
            } else {
                //if ($this->wishlistLoadRoute->isMaxWishlist($context, $customer)) {
                if (true) {
                    $this->wishlistHandleRoute->create($request, $context, $customer);
                    $success = true;
                } else {
                    $success = false;
                }

            }
        } catch (\Throwable $exception) {
            $success = false;
        }

        return new JsonResponse($success);
    }
    /**
     * 
     * @LoginRequired()
     * @Route("wishlist/remove-product-wishlist", name="frontend.wishlist.remove.product", methods={"POST"}, defaults={"XmlHttpRequest"=true})
     */
    public function ajaxRemoveProduct(Request $request, SalesChannelContext $context, CustomerEntity $customer): Response
    {
        $success = false;
        $input = $request->request->all();

        if (empty($input['wishlistPluginId'])) {
            $success = false;
            //throw new MissingRequestParameterException('Parameter id missing');
        }
        try {
            $this->removeWishlistProductRoute->delete($input['wishlistPluginId'], $context, $customer);
            $success = true;
        } catch (\Throwable $exception) {
            $success = false;
        }

        return new JsonResponse($success);
    }


    /**
     * 
     * @LoginRequired()
     * @Route("wishlist/get-by-product/{productId}", name="frontend.wishlist.get.by.product", methods={"GET", "POST"}, defaults={"XmlHttpRequest"=true})
     */
    public function ajaxGetWishListOfProduct(Request $request, SalesChannelContext $context, CustomerEntity $customer, String $productId): Response
    {
        $success = false;
        $wishlistProducts = $this->wishlistLoadRoute->getWishlistOfProduct($context, $customer, $productId);
       
        if (!empty($wishlistProducts) && !empty($wishlistProducts->getElements())) {
            $wishlistProducts = $wishlistProducts->getElements();
            $res = [];

            array_map(function ($wishlistProducts) use (&$res)  {
                $name = $wishlistProducts->wishlistPlugin->name;
                $res[$wishlistProducts->wishlistPlugin->id] =  ['name' => $name, 'wishlistProductId' => $wishlistProducts->id];
            }, $wishlistProducts);

            return new JsonResponse($res);
        }

        return new JsonResponse([]);
    }

    /**
     * 
     * @LoginRequired()
     * @Route("wishlist/get-popular-product", name="frontend.wishlist.get.popular.product", methods={"GET", "POST"}, defaults={"XmlHttpRequest"=true})
     */
    public function ajaxGetPoPularProduct(SalesChannelContext $context): Response
    {

        $products = $this->wishlistLoadRoute->getPopularProduct($context);

        $response = $this->renderStorefront('@Wishlistplugin/storefront/page/wishlist-plugin/product/popular_product.html.twig', ['searchResult' => $products]);
        $response->headers->set('x-robots-tag', 'noindex');
        return $response;
    }

    /**
     * 
     * @LoginRequired()
     * @Route("wishlist/delete-wishlist-product-add-card/{wishlistId}/{productId}", name="frontend.wishlist.delete.product.add.card", methods={"GET", "POST"}, defaults={"XmlHttpRequest"=true})
     */
    public function ajaxDeleteWishlistProductAddCart(SalesChannelContext $context,CustomerEntity $customer, String $wishlistId, String $productId): Response
    {

        if ($this->systemConfigService->get(self::DELETE_PRODUCT_AFTER_ADD_CARD)) {
            $this->removeWishlistProductRoute->deleteWishlistProduct($wishlistId, $productId,  $context,  $customer);
        }

        return new JsonResponse();
    }
}
