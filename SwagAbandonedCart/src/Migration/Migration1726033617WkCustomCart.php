<?php declare(strict_types=1);

namespace Swag\Abandoned\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('core')]
class Migration1726033617WkCustomCart extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1726033617;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `wk_custom_cart` (
                `id` BINARY(16) NOT NULL,
                `token` VARCHAR(255) NOT NULL,
                `cart` LONGTEXT NOT NULL,
                `price` DECIMAL(10, 2) NOT NULL,
                `line_item_count` VARCHAR(255) NOT NULL,
                `currency_id` BINARY(16) NOT NULL,
                `shipping_method_id` BINARY(16) NOT NULL,
                `payment_method_id` BINARY(16) NOT NULL,
                `country_id` BINARY(16) NOT NULL,
                `customer_id` BINARY(16),
                `sales_channel_id` BINARY(16) NOT NULL,
                `wildcard` VARCHAR(255) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3),
                PRIMARY KEY (`id`),
                CONSTRAINT `fk.wk_custom_cart.currency_id` FOREIGN KEY (`currency_id`)
                    REFERENCES `currency` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk.wk_custom_cart.shipping_method_id` FOREIGN KEY (`shipping_method_id`)
                    REFERENCES `shipping_method` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk.wk_custom_cart.payment_method_id` FOREIGN KEY (`payment_method_id`)
                    REFERENCES `payment_method` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk.wk_custom_cart.country_id` FOREIGN KEY (`country_id`)
                    REFERENCES `country` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                CONSTRAINT `fk.wk_custom_cart.customer_id` FOREIGN KEY (`customer_id`)
                    REFERENCES `customer` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk.wk_custom_cart.sales_channel_id` FOREIGN KEY (`sales_channel_id`)
                    REFERENCES `sales_channel` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }
}
