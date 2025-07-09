<?php declare(strict_types=1);

namespace Swag\Abandoned\ScheduledTask;

use Shopware\Core\Framework\Context;
use Swag\Abandoned\Service\AbandonedCartService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

class AbandonedCartNotificationTaskHandler extends ScheduledTaskHandler
{
   protected AbandonedCartService $abandonedCartService;
    protected SystemConfigService $systemConfigService;
   
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        AbandonedCartService $abandonedCartService, 
        SystemConfigService $systemConfigService
    )
    {
        
        parent::__construct($scheduledTaskRepository);
        $this->abandonedCartService = $abandonedCartService;
        $this->systemConfigService = $systemConfigService;
    }

    public static function getHandledMessages(): iterable
    {
        return [AbandonedCartNotificationTask::class];
    }

    public function run(): void
    {
       $context = Context::createDefaultContext();
       $this->abandonedCartService->handleTask($context);
    }
}