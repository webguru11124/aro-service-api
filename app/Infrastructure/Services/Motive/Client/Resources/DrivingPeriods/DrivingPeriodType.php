<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods;

enum DrivingPeriodType: string
{
    case DRIVING = 'driving';
    case PERSONAL_CONVEYANCE = 'pc';
    case YARD_MOVE = 'ym';
}
