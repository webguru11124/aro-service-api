<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Enums;

enum ServiceType
{
    case INITIAL;
    case RESERVICE;
    case REGULAR;
}
