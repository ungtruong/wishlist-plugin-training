<?php declare(strict_types=1);

namespace Wishlist\WishlistPlugin\Core\Checkout\Customer\SalesChannel;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SalesChannel\SuccessResponse;

abstract class AbstractWishlistHandleRoute
{
    abstract public function getDecorated(): AbstractLoadWishlistRoute;

    abstract public function create(Request $request, SalesChannelContext $context, CustomerEntity $customer) : SuccessResponse;
}
