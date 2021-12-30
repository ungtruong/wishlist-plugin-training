<?php declare(strict_types=1);

namespace Wishlist\WishlistPlugin\Core\Content\WishlistPluginProduct;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(WishlistPluginProductEntity $entity)
 * @method void               set(string $key, WishlistPluginProductEntity $entity)
 * @method WishlistPluginProductEntity[]    getIterator()
 * @method WishlistPluginProductEntity[]    getElements()
 * @method WishlistPluginProductEntity|null get(string $key)
 * @method WishlistPluginProductEntity|null first()
 * @method WishlistPluginProductEntity|null last()
 */
class WishlistPluginProductEntityCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return WishlistPluginProductEntity::class;
    }
}