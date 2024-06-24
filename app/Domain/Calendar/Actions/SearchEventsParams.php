<?php

declare(strict_types=1);

namespace App\Domain\Calendar\Actions;

use Carbon\CarbonInterface;

class SearchEventsParams
{
    public function __construct(
        public readonly CarbonInterface|null $startDate,
        public readonly CarbonInterface|null $endDate,
        public readonly int|null $officeId = null,
        public readonly string|null $searchText = null,
    ) {
    }
}
