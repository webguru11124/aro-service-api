<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Enums;

enum OptimizationEngine: string
{
    case VROOM = 'Vroom';
    case GOOGLE = 'Google Optimization AI';
}
