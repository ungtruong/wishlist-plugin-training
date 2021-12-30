<?php declare(strict_types=1);

namespace Wishlist\WishlistPlugin\Core\Content\WishlistPlugin;

use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Wishlist\WishlistPlugin\Extension\Content\Product\WishlistPluginProductDefinition;
//use Wishlist\WishlistPlugin\Core\Content\WishlistPluginProduct\WishlistPluginProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;

class WishlistPluginDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'wishlist_plugin';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return WishlistPluginEntityCollection::class;
    }
 
    public function getEntityClass(): string
    {
        return WishlistPluginEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new StringField('name', 'name'),
            //new StringField('customer_id', 'customerId'),
            (new FkField('customer_id', 'customerId', CustomerDefinition::class)),
            new CreatedAtField(),
            new UpdatedAtField(),
            //new ManyToManyAssociationField('wishlistPlugins', ProductDefinition::class, WishlistPluginProductDefinition::class, 'wishlist_plugin_id', 'product_id'),
            new ManyToManyAssociationField('products', ProductDefinition::class, WishlistPluginProductDefinition::class, 'wishlist_plugin_id', 'product_id'),
            new ManyToOneAssociationField('customers', 'customer_id', CustomerDefinition::class, 'id'),

            (new OneToManyAssociationField('wishlistProducts', WishlistPluginProductDefinition::class, 'wishlist_plugin_id', 'id'))->addFlags(new CascadeDelete()),
        ]);
    }
}