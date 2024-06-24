<?php

declare(strict_types=1);

namespace App\Application\DTO;

use Carbon\CarbonInterface;

readonly class RouteCreationDTO
{
    /**
     * @param int[] $officeIds
     * @param CarbonInterface|null $startDate
     * @param int $numDaysAfterStartDate
     * @param int $numDaysToCreateRoutes
     */
    public function __construct(
        public array $officeIds,
        public CarbonInterface|null $startDate,
        public int $numDaysAfterStartDate = 0,
        public int $numDaysToCreateRoutes = 1,
    ) {
    }
}
