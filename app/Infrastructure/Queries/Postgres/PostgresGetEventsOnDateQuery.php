<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\Postgres;

use App\Domain\Calendar\Entities\Event;
use App\Domain\Calendar\Entities\RecurringEvent;
use App\Domain\Calendar\SearchCriteria\SearchCalendarEventsCriteria;
use App\Domain\Contracts\Queries\GetEventsOnDateQuery;
use App\Domain\Contracts\Repositories\CalendarEventRepository;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PostgresGetEventsOnDateQuery implements GetEventsOnDateQuery
{
    public function __construct(
        private CalendarEventRepository $eventRepository,
    ) {
    }

    /**
     * Get events for a given date
     *
     * @param int $officeId
     * @param CarbonInterface $date
     *
     * @return Collection<RecurringEvent>
     */
    public function get(int $officeId, CarbonInterface $date): Collection
    {
        return $this->eventRepository->search(new SearchCalendarEventsCriteria(
            officeId: $officeId,
        ))->map(
            fn (Event $event) => $event->getRecurringEventOnDate($date)
        )->filter();
    }
}
