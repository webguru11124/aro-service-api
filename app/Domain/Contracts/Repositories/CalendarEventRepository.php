<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repositories;

use App\Domain\Calendar\Entities\Event;
use App\Domain\Calendar\Exceptions\EventNotFoundException;
use App\Domain\Calendar\SearchCriteria\SearchCalendarEventsCriteria;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface CalendarEventRepository
{
    /**
     * @param int $id
     *
     * @return Event
     * @throws EventNotFoundException
     */
    public function find(int $id): Event;

    /**
     * @return Collection<Event>
     */
    public function findAll(): Collection;

    /**
     * @param SearchCalendarEventsCriteria $criteria
     *
     * @return Collection<Event>
     */
    public function search(SearchCalendarEventsCriteria $criteria): Collection;

    /**
     * @param int $officeId
     * @param CarbonInterface $date
     *
     * @return Collection<Event>
     */
    public function searchByOfficeIdAndDate(int $officeId, CarbonInterface $date): Collection;

    /**
     * @param int $id
     *
     * @return void
     */
    public function delete(int $id): void;

    /**
     * @param Event $event
     *
     * @return int
     */
    public function create(Event $event): int;

    /**
     * @param Event $event
     *
     * @return void
     */
    public function update(Event $event): void;

    /**
     * @return int
     */
    public function getEventOverridesNextId(): int;
}
