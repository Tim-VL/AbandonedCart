<?php declare(strict_types=1);

namespace Swag\Abandoned\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class AbandonedCartNotificationTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'swag.abandoned_cart_notification_task';
    }

    public static function getDefaultInterval(): int
    {
        return 300; // 5 minutes
    }
}