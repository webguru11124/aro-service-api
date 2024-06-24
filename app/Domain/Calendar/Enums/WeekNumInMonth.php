<?php

declare(strict_types=1);

namespace App\Domain\Calendar\Enums;

enum WeekNumInMonth: int
{
    case FIRST = 1;
    case SECOND = 2;
    case THIRD = 3;
    case FOURTH = 4;
    case LAST = 5; // There can be up to 6 week in month, if 5 week selected it will be treated as last week in month
}
