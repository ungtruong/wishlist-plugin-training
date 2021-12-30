<?php declare(strict_types=1);

namespace Wishlist\WishlistPlugin\DataResolver;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Wishlist\WishlistPlugin\Core\Checkout\Customer\SalesChannel\AbstractLoadWishlistRoute;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Content\Cms\SalesChannel\Struct\ProductSliderStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ProductPopularSliderCmsElementResolver extends AbstractCmsElementResolver

{
    private SystemConfigService $systemConfigService;

    private AbstractLoadWishlistRoute $wishlistLoadRoute;


    public function __construct(SystemConfigService $systemConfigService, AbstractLoadWishlistRoute $wishlistLoadRoute)
    {
        $this->systemConfigService = $systemConfigService;
        $this->wishlistLoadRoute = $wishlistLoadRoute;
    }
    public function getType(): string
    {
        return 'product-popular-slider';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        
        return null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $slider = new ProductSliderStruct();
        $slot->setData($slider);
        $products = $this->getPopularProduct($slider, $result,  $resolverContext->getSalesChannelContext());
        if (empty($products)) {
            return;
        }
        $slider->setProducts($products->getEntities());
    }

    private function getPopularProduct(ProductSliderStruct $slider, ElementDataCollection $result, SalesChannelContext $saleschannelContext) 
    {
        return $this->wishlistLoadRoute->getPopularProduct($saleschannelContext);
    }
}