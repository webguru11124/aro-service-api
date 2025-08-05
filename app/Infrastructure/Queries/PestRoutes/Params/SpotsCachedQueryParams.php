<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\PestRoutes\Params;

use Carbon\CarbonInterface;

class SpotsCachedQueryParams extends AbstractCachedQueryParams
{
    public function __construct(
        int $officeId,
        CarbonInterface|null $startDate = null,
        CarbonInterface|null $endDate = null,
        public bool $apiCanSchedule = true,
    ) {
        parent::__construct($officeId, $startDate, $endDate);
    }
}
