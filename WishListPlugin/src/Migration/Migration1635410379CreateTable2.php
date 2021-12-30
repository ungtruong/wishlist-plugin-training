<?php declare(strict_types=1);

namespace Wishlist\WishlistPlugin\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1635410379CreateTable2 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1635410379;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
        CREATE TABLE IF NOT EXISTS `wishlist_plugin` (
            `id` BINARY(16) NOT NULL,
            `name` VARCHAR(255) NOT NULL,
            `customer_id` BINARY(16)  NULL DEFAULT NULL,
            `sales_channel_id` BINARY(16)  NULL DEFAULT NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) NULL DEFAULT NULL,
            PRIMARY KEY (`id`)

        )
            ENGINE = InnoDB
            DEFAULT CHARSET = utf8mb4
            COLLATE = utf8mb4_unicode_ci;
        SQL;
        $connection->executeStatement($query);

        $query = <<<SQL
        CREATE TABLE IF NOT EXISTS `wishlist_plugin_product` (
            `id` BINARY(16) NOT NULL,
            `wishlist_plugin_id` BINARY(16) NOT NULL,
            `product_id` BINARY(16) NULL,
            `quantity` INT NOT NULL DEFAULT 0,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) NULL DEFAULT NULL,

            PRIMARY KEY (`id`)
        )
            ENGINE = InnoDB
            DEFAULT CHARSET = utf8mb4
            COLLATE = utf8mb4_unicode_ci;
        SQL;
        $connection->executeStatement($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
