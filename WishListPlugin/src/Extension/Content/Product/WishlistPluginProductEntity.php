<?php declare(strict_types=1);

namespace Wishlist\WishlistPlugin\Extension\Content\Product;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class WishlistPluginProductEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $wishlistPluginId;

    /**
     * @var string
     */
    protected $productId;

    /**
     * @var int
     */
    protected $quantity;

    public function getwishlistPluginId(): string
    {
        return $this->wishlistPluginId;
    }

    public function setwishlistPluginId(string $wishlistPluginId): void
    {
        $this->wishlistPluginId = $wishlistPluginId;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }
    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): void
    {
        $this->quantity = $quantity;
    }
    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }
}