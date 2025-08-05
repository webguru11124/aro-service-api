<?php

declare(strict_types=1);

namespace App\Domain\Calendar\Enums;

enum ScheduleInterval: string
{
    case ONCE = 'once';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case MONTHLY_ON_DAY = 'monthly-on-day';
    case YEARLY = 'yearly';
}
