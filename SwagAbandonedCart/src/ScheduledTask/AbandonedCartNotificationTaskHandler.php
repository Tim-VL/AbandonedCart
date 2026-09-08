<?php declare(strict_types=1);

namespace Swag\Abandoned\ScheduledTask;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\Abandoned\Service\AbandonedCartService;

class AbandonedCartNotificationTaskHandler extends ScheduledTaskHandler
{
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $exceptionLogger,
        private readonly AbandonedCartService $abandonedCartService,
        private readonly SystemConfigService $systemConfigService
    ) {
        // Shopware 6.7 requires the exception logger.
        parent::__construct(
            $scheduledTaskRepository,
            $exceptionLogger
        );
    }

    public static function getHandledMessages(): iterable
    {
        return [
            AbandonedCartNotificationTask::class,
        ];
    }

    public function run(): void
    {
        // Scheduled tasks run outside a normal HTTP request.
        $context = Context::createDefaultContext();

        $this->abandonedCartService->handleTask($context);
    }
}