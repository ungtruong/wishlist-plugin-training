<?php declare(strict_types=1);

namespace Wishlist\WishlistPlugin\Core\Content\WishlistPlugin;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(WishlistPluginEntity $entity)
 * @method void               set(string $key, WishlistPluginEntity $entity)
 * @method WishlistPluginEntity[]    getIterator()
 * @method WishlistPluginEntity[]    getElements()
 * @method WishlistPluginEntity|null get(string $key)
 * @method WishlistPluginEntity|null first()
 * @method WishlistPluginEntity|null last()
 */
class WishlistPluginEntityCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return WishlistPluginEntity::class;
    }
}