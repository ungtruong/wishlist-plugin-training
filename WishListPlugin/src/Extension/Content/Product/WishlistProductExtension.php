<?php declare(strict_types=1);

namespace Wishlist\WishlistPlugin\Extension\Content\Product;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Wishlist\WishlistPlugin\Core\Content\WishlistPlugin\WishlistPluginDefinition;

class WishlistProductExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new OneToManyAssociationField('wishlistProducts', WishlistPluginProductDefinition::class, 'product_id'),
        );
        $collection->add(
            new ManyToManyAssociationField('wishlistPlugins', 
                                            WishlistPluginDefinition::class, 
                                            WishlistPluginProductDefinition::class, 
                                            'product_id', 
                                            'wishlist_plugin_id')
        );
    }

    public function getDefinitionClass(): string
    {
        return ProductDefinition::class;
    }
}
