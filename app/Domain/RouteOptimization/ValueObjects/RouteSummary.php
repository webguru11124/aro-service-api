<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\ValueObjects;

use App\Domain\SharedKernel\ValueObjects\Duration;
use Carbon\CarbonInterface;

readonly class RouteSummary
{
    public function __construct(
        public Duration|null $drivingTime,
        public Duration|null $servicingTime,
        public Duration|null $totalWorkingTime,
        public CarbonInterface $asOf,
        public bool $excludeFirstAppointment
    ) {
    }
}
