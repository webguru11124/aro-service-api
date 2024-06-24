<?php

declare(strict_types=1);

namespace App\Application\DTO;

use Carbon\CarbonInterface;

readonly class RouteOptimizationDTO
{
    /**
     * @param int[] $officeIds
     * @param CarbonInterface|null $startDate
     * @param int $numDaysAfterStartDate
     * @param int $numDaysToOptimize
     * @param bool $lastOptimizationRun
     * @param bool $simulationRun
     * @param bool $buildPlannedOptimization
     * @param string[] $disabledRules
     */
    public function __construct(
        public array $officeIds,
        public CarbonInterface|null $startDate,
        public int $numDaysAfterStartDate = 0,
        public int $numDaysToOptimize = 0,
        public bool $lastOptimizationRun = false,
        public bool $simulationRun = false,
        public bool $buildPlannedOptimization = false,
        public array $disabledRules = [],
    ) {
    }
}
