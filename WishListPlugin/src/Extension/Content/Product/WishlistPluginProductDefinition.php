<?php declare(strict_types=1);

namespace Wishlist\WishlistPlugin\Extension\Content\Product;

use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Wishlist\WishlistPlugin\Core\Content\WishlistPlugin\WishlistPluginDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;

class WishlistPluginProductDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'wishlist_plugin_product';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return WishlistPluginProductEntityCollection::class;
    }
 
    public function getEntityClass(): string
    {
        return WishlistPluginProductEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new IntField('quantity', 'quantity'),
            //new StringField('wishlist_plugin_id', 'wishlistPluginId'),
            (new FkField('wishlist_plugin_id', 'wishlistPluginId', WishlistPluginDefinition::class)),
            //new StringField('product_id', 'productId'),
            (new FkField('product_id', 'productId', ProductDefinition::class)),
            new CreatedAtField(),
            new UpdatedAtField(),
            
            new ManyToOneAssociationField('wishlistPlugin', 'wishlist_plugin_id', WishlistPluginDefinition::class, 'id'),
            new ManyToOneAssociationField('products', 'product_id', ProductDefinition::class, 'id')
        ]);
    }
}