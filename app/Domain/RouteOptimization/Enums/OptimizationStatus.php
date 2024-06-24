<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Enums;

enum OptimizationStatus: string
{
    case POST = 'Post Optimization';
    case PRE = 'Pre Optimization';
    case PLAN = 'Planned Optimization';
    case SIMULATION = 'Simulated Optimization';
}
