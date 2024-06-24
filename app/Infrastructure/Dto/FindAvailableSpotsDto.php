<?php

declare(strict_types=1);

namespace App\Infrastructure\Dto;

use App\Domain\SharedKernel\Entities\Office;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Carbon\CarbonInterface;

class FindAvailableSpotsDto
{
    public function __construct(
        public readonly Office $office,
        public readonly Coordinate $coordinate,
        public readonly bool $isInitial,
        public readonly int|null $responseLimit,
        public readonly int $distanceThreshold,
        public readonly CarbonInterface $startDate,
        public readonly CarbonInterface $endDate,
        public bool $skipCache = false,
    ) {
    }
}
