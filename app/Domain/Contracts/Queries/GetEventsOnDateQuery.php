<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Queries;

use App\Domain\Calendar\Entities\RecurringEvent;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface GetEventsOnDateQuery
{
    /**
     * Get events for a given date
     *
     * @param int $officeId
     * @param CarbonInterface $date
     *
     * @return Collection<RecurringEvent>
     */
    public function get(int $officeId, CarbonInterface $date): Collection;
}
