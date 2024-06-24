<?php

declare(strict_types=1);

namespace App\Domain\Calendar\Entities;

use App\Domain\Calendar\Enums\EventType;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\Enums\WeekDay;
use App\Domain\Calendar\IntervalStrategies\AbstractIntervalStrategy;
use App\Domain\Calendar\ValueObjects\EventDetails;
use App\Domain\Calendar\ValueObjects\RecurringEventId;
use App\Domain\Calendar\ValueObjects\WeeklyOccurrence;
use App\Domain\SharedKernel\Exceptions\InvalidTimeWindowException;
use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Domain\SharedKernel\ValueObjects\Timezone;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonTimeZone;
use Illuminate\Support\Collection;

class RecurringEvent
{
    private TimeWindow $timeWindow;

    /**
     * @param RecurringEventId $identity
     * @param EventDetails $eventDetails
     * @param Collection<int> $participantIds
     * @param Override|null $override
     *
     * @throws InvalidTimeWindowException
     */
    public function __construct(
        private readonly RecurringEventId $identity,
        private readonly EventType $eventType,
        private readonly EventDetails $eventDetails,
        private readonly Collection $participantIds,
        private readonly AbstractIntervalStrategy $intervalStrategy,
        private readonly Override|null $override
    ) {
        $this->timeWindow = new TimeWindow(
            Carbon::parse(
                $this->identity->getDate()->toDateString() . ' ' . $this->eventDetails->getStartTime(),
                $this->eventDetails->getTimeZone()
            ),
            Carbon::parse(
                $this->identity->getDate()->toDateString() . ' ' . $this->eventDetails->getEndTime(),
                $this->eventDetails->getTimeZone()
            ),
        );
    }

    public function getIdentity(): RecurringEventId
    {
        return $this->identity;
    }

    /**
     * @return EventType
     */
    public function getEventType(): EventType
    {
        return $this->eventType;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->override?->getEventDetails()->getTitle() ?? $this->eventDetails->getTitle();
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->override?->getEventDetails()->getDescription() ?? $this->eventDetails->getDescription();
    }

    /**
     * @return TimeWindow
     */
    public function getTimeWindow(): TimeWindow
    {
        return $this->timeWindow;
    }

    /**
     * @return Coordinate|null
     */
    public function getLocation(): Coordinate|null
    {
        return $this->override?->getEventDetails()->getLocation() ?? $this->eventDetails->getLocation();
    }

    /**
     * @return Collection<int>
     */
    public function getParticipantIds(): Collection
    {
        return $this->participantIds;
    }

    /**
     * @return Address|null
     */
    public function getAddress(): Address|null
    {
        return $this->override?->getEventDetails()->getAddress() ?? $this->eventDetails->getAddress();
    }

    /**
     * @return string|null
     */
    public function getMeetingLink(): string|null
    {
        return $this->override?->getEventDetails()->getMeetingLink() ?? $this->eventDetails->getMeetingLink();
    }

    /**
     * @return string
     */
    public function getStartTime(): string
    {
        return $this->override?->getEventDetails()->getStartTime() ?? $this->eventDetails->getStartTime();
    }

    /**
     * @return string
     */
    public function getEndTime(): string
    {
        return $this->override?->getEventDetails()->getEndTime() ?? $this->eventDetails->getEndTime();
    }

    /**
     * @return bool
     */
    public function isCanceled(): bool
    {
        return $this->override?->isCanceled() ?? false;
    }

    /**
     * @return int|null
     */
    public function getOverrideId(): int|null
    {
        return $this->override?->getId();
    }

    /**
     * @return CarbonInterface
     */
    public function getStartDate(): CarbonInterface
    {
        return $this->intervalStrategy->getStartDate();
    }

    /**
     * @return CarbonInterface|null
     */
    public function getEndDate(): CarbonInterface|null
    {
        return $this->intervalStrategy->getEndDate();
    }

    /**
     * @return int
     */
    public function getRepeatEvery(): int
    {
        return $this->intervalStrategy->getRepeatEvery();
    }

    /**
     * @return ScheduleInterval
     */
    public function getInterval(): ScheduleInterval
    {
        return $this->intervalStrategy->getInterval();
    }

    /**
     * @return WeeklyOccurrence|null
     */
    public function getWeeklyOccurrence(): WeeklyOccurrence|null
    {
        return $this->intervalStrategy->getOccurrence();
    }

    /**
     * @return CarbonTimeZone
     */
    public function getTimeZone(): CarbonTimeZone
    {
        return $this->eventDetails->getTimeZone();
    }

    /**
     * Returns list of week days as CSV string
     *
     * @return string|null
     */
    public function getWeekDaysAsCsv(): string|null
    {
        if (!$this->getWeeklyOccurrence()) {
            return null;
        }

        return $this->getWeeklyOccurrence()->weekDays->implode(fn (WeekDay $weekDay) => $weekDay->value, ',');
    }

    /**
     * @return int|null
     */
    public function getWeekNum(): int|null
    {
        return $this->getWeeklyOccurrence()?->weekNumInMonth?->value;
    }

    /**
     * Returns full name of the timezone
     *
     * @return string
     */
    public function getTimezoneFullName(): string
    {
        return (new Timezone($this->getTimeZone()->getName()))->getTimezoneFullName();
    }

    /**
     * It checks if the employee is invited to the event
     *
     * @param int $employeeId
     *
     * @return bool
     */
    public function isEmployeeInvited(int $employeeId): bool
    {
        return $this->participantIds->contains($employeeId);
    }

    /**
     * @return CarbonInterface
     */
    public function getDate(): CarbonInterface
    {
        return $this->getIdentity()->getDate();
    }
}
