<?php

declare(strict_types=1);

namespace App\Domain\Calendar\Actions;

use App\Domain\Calendar\Entities\Event;
use App\Domain\Calendar\Entities\RecurringEvent;
use App\Domain\Calendar\SearchCriteria\SearchCalendarEventsCriteria;
use App\Domain\Contracts\Repositories\CalendarEventRepository;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class SearchEvents
{
    /** @var Collection<Event> */
    private Collection $calendarEvents;
    private SearchEventsParams $dto;

    public function __construct(
        private readonly CalendarEventRepository $eventRepository,
    ) {
    }

    /**
     * @param SearchEventsParams $dto
     *
     * @return Collection<RecurringEvent>
     */
    public function __invoke(SearchEventsParams $dto): Collection
    {
        $this->dto = $dto;
        $this->calendarEvents = $this->eventRepository->search(new SearchCalendarEventsCriteria(
            officeId: $dto->officeId,
            title: $dto->searchText
        ));

        if (!empty($this->dto->startDate) && !empty($this->dto->endDate)) {
            return $this->getRecurringEvents();
        }

        return $this->getNearestEvents();
    }

    /**
     * @return Collection<RecurringEvent>
     */
    private function getRecurringEvents(): Collection
    {
        $allEvents = new Collection();

        for ($date = $this->dto->startDate->clone(); $date->lessThan($this->dto->endDate); $date->addDay()) {
            $particularEventsForDay = $this->getParticularEventsOnDay($date);

            if ($particularEventsForDay->isEmpty()) {
                continue;
            }

            $allEvents = $allEvents->merge($particularEventsForDay);
        }

        return $allEvents;
    }

    /**
     * @param CarbonInterface $date
     *
     * @return Collection<RecurringEvent>
     */
    private function getParticularEventsOnDay(CarbonInterface $date): Collection
    {
        $events = new Collection();

        /** @var Event $calendarEvent */
        foreach ($this->calendarEvents as $calendarEvent) {
            $officeDate = Carbon::parse($date->toDateString(), $calendarEvent->getEventDetails()->getTimeZone());
            $recurringEvent = $calendarEvent->getRecurringEventOnDate($officeDate);

            if ($recurringEvent !== null && $this->isRecurringEventMatchesCriteria($recurringEvent)) {
                $events->add($recurringEvent);
            }
        }

        return $events;
    }

    private function isRecurringEventMatchesCriteria(RecurringEvent $recurringEvent): bool
    {
        return $this->isRecurringEventMatchesSearchText($recurringEvent);
    }

    private function isRecurringEventMatchesSearchText(RecurringEvent $recurringEvent): bool
    {
        if (empty($this->dto->searchText)) {
            return true;
        }

        $title = $recurringEvent->getTitle();
        $searchString = $this->dto->searchText;

        return stripos($title, $searchString) !== false;
    }

    /**
     * @return Collection<RecurringEvent>
     */
    private function getNearestEvents(): Collection
    {
        $events = new Collection();

        /** @var Event $event */
        foreach ($this->calendarEvents as $event) {
            $date = Carbon::today($event->getEventDetails()->getTimeZone());
            $nextEvent = $event->getNextOccurrence($date);

            if (!is_null($nextEvent)) {
                $events->add($nextEvent);

                continue;
            }

            $prevEvent = $event->getPrevOccurrence($date);

            if (!is_null($prevEvent)) {
                $events->add($prevEvent);
            }
        }

        return $events;
    }
}
