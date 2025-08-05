<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Queries;

use App\Domain\Calendar\Entities\Participant;
use App\Domain\Calendar\Entities\Event;
use Illuminate\Support\Collection;

interface EventParticipantQuery
{
    /**
     * Fetches participants for a given event.
     *
     * @param Event $event
     *
     * @return Collection<Participant>
     */
    public function find(Event $event): Collection;
}
