<?php

declare(strict_types=1);

namespace App\Application\Services\Calendar;

use App\Application\DTO\EventDTO;
use App\Domain\Calendar\Entities\Employee;
use App\Domain\Calendar\Entities\Event;
use App\Domain\Calendar\Entities\Override;
use App\Domain\Calendar\Enums\EventType;
use App\Domain\Calendar\Exceptions\OverrideOutOfEventRecurrenceException;
use App\Domain\Calendar\IntervalStrategies\IntervalStrategyFactory;
use App\Domain\Calendar\ValueObjects\EventDetails;
use App\Domain\Calendar\ValueObjects\EventEnd;
use App\Domain\Calendar\ValueObjects\WeeklyOccurrence;
use App\Domain\Contracts\Repositories\CalendarEventRepository;
use App\Domain\Contracts\Queries\Office\OfficeEmployeeQuery;
use Carbon\Carbon;

class CalendarService
{
    public function __construct(
        private CalendarEventRepository $eventRepository,
        private IntervalStrategyFactory $intervalStrategyFactory,
        private OfficeEmployeeQuery $officeEmployeeQuery,
    ) {
    }

    /**
     * Update Calendar event with new override
     *
     * @param Event $event
     * @param Override $override
     *
     * @return void
     * @throws OverrideOutOfEventRecurrenceException
     */
    public function updateCalendarEventOverride(
        Event $event,
        Override $override,
    ): void {
        $this->validateEventDate($event, $override);
        $event->addOverrides(collect([$override]));
        $this->eventRepository->update($event);
    }

    /**
     * @throws OverrideOutOfEventRecurrenceException
     */
    private function validateEventDate(Event $event, Override $override): void
    {
        if (!$event->isScheduledOnDate($override->getDate())) {
            throw OverrideOutOfEventRecurrenceException::instance();
        }
    }

    /**
     * Removes participants from the event
     *
     * @param Event $event
     * @param int ...$participantIds
     *
     * @return void
     */
    public function deleteEventParticipants(Event $event, int ...$participantIds): void
    {
        $event = $event->removeParticipantIds(...$participantIds);
        $this->eventRepository->update($event);
    }

    /**
     * Updates event participants
     *
     * @param Event $event
     * @param int[] $newParticipantIds
     *
     * @return void
     */
    public function updateEventParticipants(Event $event, array $newParticipantIds): void
    {
        $employees = $this->officeEmployeeQuery->find($event->getOfficeId());

        $validParticipantIds = $employees->map(
            fn (Employee $employee) => $employee->getId()
        )->intersect($newParticipantIds)->all();

        $event->addParticipantIds($validParticipantIds);
        $this->eventRepository->update($event);
    }

    /**
     * Creates new event
     *
     * @param EventDTO $dto
     *
     * @return int
     */
    public function createEvent(EventDTO $dto): int
    {
        return $this->eventRepository->create(
            $this->makeEventFromDto($dto)
        );
    }

    private function makeEventFromDto(EventDTO $dto): Event
    {
        $startDate = Carbon::parse($dto->startDate, $dto->timeZone);
        $endDate = $dto->endDate === null
            ? null
            : Carbon::parse($dto->endDate, $dto->timeZone);
        $eventEnd = new EventEnd(
            $dto->endAfter,
            $endDate,
            $dto->occurrences
        );

        return new Event(
            id: $this->eventRepository->getEventOverridesNextId(),
            officeId: $dto->officeId,
            eventType: EventType::from($dto->eventType),
            intervalStrategy: $this->intervalStrategyFactory->getIntervalStrategy(
                $startDate,
                $eventEnd,
                $dto->interval,
                $dto->repeatEvery,
                new WeeklyOccurrence($dto->weekDays),
                $dto->weekNumber,
            ),
            eventDetails: new EventDetails(
                title: $dto->title,
                description: $dto->description,
                startTime: $dto->startTime,
                endTime: $dto->endTime,
                timeZone: $dto->timeZone,
                location: $dto->location,
                meetingLink: $dto->meetingLink,
                address: $dto->address
            ),
        );
    }
}
