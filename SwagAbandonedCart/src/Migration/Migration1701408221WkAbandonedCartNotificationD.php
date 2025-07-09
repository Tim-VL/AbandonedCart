<?php declare(strict_types=1);

namespace Swag\Abandoned\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1701408221WkAbandonedCartNotificationD extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1701408221;
    }

    public function update(Connection $connection): void
    {
        // implement update
        $connection->executeStatement("CREATE TABLE IF NOT EXISTS `wk_abandoned_cart_notification` (
            `id` BINARY(16) NOT NULL PRIMARY KEY,
            `customer_id` BINARY(16) NOT NULL,
            `cart_items` LONGBLOB NOT NULL,
            `times_notified` INT DEFAULT 0,
            `times_handler_notified` INT DEFAULT 0,
            `last_notified` DATETIME(3) DEFAULT NULL,
            `next_notified` DATETIME(3) DEFAULT NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) DEFAULT NULL,
            KEY `customer_id` (`customer_id`),
            CONSTRAINT `wk_abandoned_cart_notification_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
