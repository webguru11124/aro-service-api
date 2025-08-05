<?php

declare(strict_types=1);

namespace App\Application\Services\Calendar;

use App\Domain\Calendar\Entities\Event;
use App\Domain\Contracts\Queries\EventParticipantQuery;
use Illuminate\Support\Collection;

//TODO: Rewrite after new avatar service is implemented
class CalendarEventParticipantsService
{
    public function __construct(
        private readonly EventParticipantQuery $eventParticipantQuery,
    ) {
    }

    /**
     * Gets participants for the event
     *
     * @param Event $event
     *
     * @return Collection
     */
    public function getParticipants(Event $event): Collection
    {
        return $this->eventParticipantQuery->find($event);
    }
}
