<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Enums;

enum MetricKey: string
{
    case OPTIMIZATION_SCORE = 'optimization_score';
    case TOTAL_WEIGHTED_SERVICES = 'total_weighted_services';
    case TOTAL_WORKING_HOURS = 'total_working_hours';
    case TOTAL_DRIVE_TIME = 'total_drive_time';
    case TOTAL_DRIVE_MILES = 'total_drive_miles';
    case AVERAGE_WEIGHTED_SERVICES_PER_HOUR = 'average_weighted_services_per_hour';
    case AVERAGE_TIME_BETWEEN_SERVICES = 'average_time_between_services';
    case AVERAGE_MILES_BETWEEN_SERVICES = 'average_miles_between_services';
}
