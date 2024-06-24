<?php

declare(strict_types=1);

namespace App\Domain\Calendar\SearchCriteria;

class SearchCalendarEventsCriteria
{
    public function __construct(
        public readonly int|null $officeId = null,
        public readonly string|null $title = null,
    ) {
    }
}
