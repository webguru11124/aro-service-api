<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods;

enum DrivingPeriodStatus: string
{
    case COMPLETE = 'complete';
    case IN_PROGRESS = 'in_progress';
    case INTERRUPTED = 'interrupted';
}
