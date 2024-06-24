<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\PestRoutes\Params;

use Carbon\CarbonInterface;

abstract class AbstractCachedQueryParams
{
    public function __construct(
        public int $officeId,
        public CarbonInterface|null $startDate = null,
        public CarbonInterface|null $endDate = null,
    ) {
    }
}
