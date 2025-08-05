<?php

declare(strict_types=1);

namespace App\Application\DTO;

use Carbon\CarbonInterface;

readonly class ScheduleAppointmentsDTO
{
    /**
     * @param int[] $officeIds
     * @param CarbonInterface|null $startDate
     * @param int $numDaysAfterStartDate
     */
    public function __construct(
        public array $officeIds,
        public CarbonInterface|null $startDate,
        public int $numDaysAfterStartDate = 0,
        public int $numDaysToSchedule = 0,
        public bool $runSubsequentOptimization = false,
    ) {
    }
}
