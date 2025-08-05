<?php

declare(strict_types=1);

namespace App\View\Shared;

use App\Domain\RouteOptimization\Enums\WorkEventType;

class WorkEventIcons
{
    public const WORK_EVENT_ICONS = [
        WorkEventType::START_LOCATION->value => '🏠',
        WorkEventType::END_LOCATION->value => '🏁',
        WorkEventType::APPOINTMENT->value => '📍',
        WorkEventType::BREAK->value => '☕',
        WorkEventType::LUNCH->value => '🥪️',
        WorkEventType::EXTRA_WORK->value => '❓',
        WorkEventType::TRAVEL->value => '🚙',
        WorkEventType::WAITING->value => '💤',
        WorkEventType::RESERVED_TIME->value => '⚠',
        WorkEventType::MEETING->value => '🏢',
    ];
}
