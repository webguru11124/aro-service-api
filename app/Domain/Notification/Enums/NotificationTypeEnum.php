<?php

declare(strict_types=1);

namespace App\Domain\Notification\Enums;

enum NotificationTypeEnum: string
{
    case OPTIMIZATION_FAILED = 'failure_optimization';
    case SCORE_OPTIMIZATION = 'score_optimization';
    case OPTIMIZATION_SKIPPED = 'optimization_skipped';
    case SCHEDULING_FAILED = 'scheduling_failed';
    case SCHEDULING_SKIPPED = 'scheduling_skipped';
    case ROUTE_EXCLUDED = 'route_excluded';
}
