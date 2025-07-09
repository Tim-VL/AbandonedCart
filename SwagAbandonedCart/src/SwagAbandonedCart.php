<?php declare(strict_types=1);

namespace Swag\Abandoned;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class SwagAbandonedCart extends Plugin
{
    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);
        if ($uninstallContext->keepUserData()) {
            return;
        }
        $connection = $this->container->get(Connection::class);
        $connection->executeUpdate("DROP TABLE IF EXISTS `wk_abandoned_cart_notification`");
        $connection->executeUpdate("DROP TABLE IF EXISTS `wk_custom_cart`");
        $connection->executeUpdate('DELETE FROM `system_config` where configuration_key LIKE "SwagAbandonedCart.config%"');
    }
}