<?php declare(strict_types=1);

namespace Wishlist\WishlistPlugin;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

class WishlistPlugin extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
 
        $config = $this->container->get('Shopware\Core\System\SystemConfig\SystemConfigService');
        $config->set('core.cart.wishlistEnabled', true);
    }
    public function update(UpdateContext $updateContext): void
    {
        parent::install($updateContext);
 
        $config = $this->container->get('Shopware\Core\System\SystemConfig\SystemConfigService');
        $config->set('core.cart.wishlistEnabled', true);
    }

}