<?php

declare(strict_types=1);

namespace App\Domain\Calendar\Entities;

use App\Domain\Calendar\Enums\EndAfter;
use App\Domain\Calendar\Enums\EventType;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\Enums\WeekDay;
use App\Domain\Calendar\IntervalStrategies\AbstractIntervalStrategy;
use App\Domain\Calendar\ValueObjects\EventDetails;
use App\Domain\Calendar\ValueObjects\RecurringEventId;
use App\Domain\Calendar\ValueObjects\WeeklyOccurrence;
use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class Event
{
    /** @var Collection<int> */
    private Collection $participantIds;

    /** @var Collection<Override>  */
    private Collection $overrides;

    public function __construct(
        private int $id,
        private int $officeId,
        private EventType $eventType,
        private AbstractIntervalStrategy $intervalStrategy,
        private EventDetails $eventDetails,
    ) {
        $this->participantIds = new Collection();
        $this->overrides = new Collection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getOfficeId(): int
    {
        return $this->officeId;
    }

    /**
     * @return EventType
     */
    public function getEventType(): EventType
    {
        return $this->eventType;
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
     * Returns list of week days as CSV string
     *
     * @return string|null
     */
    public function getWeeklyOccurrencesAsCsv(): string|null
    {
        if (!$this->getWeeklyOccurrence()) {
            return null;
        }

        return $this->getWeeklyOccurrence()->weekDays->implode(fn (WeekDay $weekDay) => $weekDay->value, ',');
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
     * @return EndAfter
     */
    public function getEndAfter(): EndAfter
    {
        return $this->intervalStrategy->getEndAfter();
    }

    /**
     * @return int
     */
    public function getRepeatEvery(): int
    {
        return $this->intervalStrategy->getRepeatEvery();
    }

    /**
     * @return int|null
     */
    public function getMaxOccurrences(): int|null
    {
        return $this->intervalStrategy->getMaxOccurrences();
    }

    /**
     * Returns event on given date if it scheduled
     *
     * @param CarbonInterface $date
     *
     * @return RecurringEvent|null
     */
    public function getRecurringEventOnDate(CarbonInterface $date): RecurringEvent|null
    {
        if (!$this->isScheduledOnDate($date)) {
            return null;
        }

        return $this->buildRecurringEvent($date);
    }

    /**
     * Event is held on date if it is scheduled on the date and not cancelled
     *
     * @param CarbonInterface $date
     *
     * @return bool
     */
    public function isHeldOnDate(CarbonInterface $date): bool
    {
        return $this->isScheduledOnDate($date) && !$this->isCanceledOnDate($date);
    }

    /**
     * @param CarbonInterface $date
     *
     * @return bool
     */
    public function isScheduledOnDate(CarbonInterface $date): bool
    {
        return $this->intervalStrategy->isScheduledOnDate($date);
    }

    /**
     * @param CarbonInterface $date
     *
     * @return bool
     */
    public function isCanceledOnDate(CarbonInterface $date): bool
    {
        $override = $this->getOverrideOnDate($date);

        return $override && $override->isCanceled();
    }

    /**
     * @param int[] $participantIds
     *
     * @return self
     */
    public function addParticipantIds(array $participantIds): self
    {
        foreach ($participantIds as $participantId) {
            if (!$this->participantIds->contains($participantId)) {
                $this->participantIds->add($participantId);
            }
        }

        return $this;
    }

    /**
     * @param int ...$participantIds
     *
     * @return $this
     */
    public function removeParticipantIds(int ...$participantIds): self
    {
        $this->participantIds = $this->participantIds->diff($participantIds);

        return $this;
    }

    /**
     * @param int $participantId
     *
     * @return bool
     */
    public function isParticipated(int $participantId): bool
    {
        return $this->participantIds->contains($participantId);
    }

    /**
     * @return Collection<int>
     */
    public function getParticipantIds(): Collection
    {
        return $this->participantIds;
    }

    /**
     * One calendar event can have multiple overrides but only one per date
     *
     * @param Collection<Override> $overrides
     *
     * @return $this
     */
    public function addOverrides(Collection $overrides): self
    {
        foreach ($overrides as $newOverride) {
            /** @var Override|null $existingOverride */
            $existingOverride = $this->overrides->first(function (Override $override) use ($newOverride) {
                return $override->getDate()->equalTo($newOverride->getDate());
            });

            if ($existingOverride) {
                // If an override with the same date exists, filter it out
                $this->overrides = $this->overrides->reject(function (Override $override) use ($existingOverride) {
                    return $override->getDate()->equalTo($existingOverride->getDate());
                });
            }

            $this->overrides->add($newOverride);
        }

        return $this;
    }

    /**
     * @return EventDetails
     */
    public function getEventDetails(): EventDetails
    {
        return $this->eventDetails;
    }

    /**
     * @return Collection<Override>
     */
    public function getOverrides(): Collection
    {
        return $this->overrides;
    }

    /**
     * Returns Override on the date if exist
     *
     * @param CarbonInterface $date
     *
     * @return Override|null
     */
    public function getOverrideOnDate(CarbonInterface $date): Override|null
    {
        return $this->overrides->first(fn (Override $override) => $date->isSameDay($override->getDate()));
    }

    /**
     * Returns next occurrence after the given date
     *
     * @param CarbonInterface $date
     *
     * @return RecurringEvent|null
     */
    public function getNextOccurrence(CarbonInterface $date): RecurringEvent|null
    {
        $nextDate = $this->intervalStrategy->getNextOccurrenceDate($date);

        if (is_null($nextDate)) {
            return null;
        }

        return $this->buildRecurringEvent($nextDate);
    }

    /**
     * Returns previous occurrence before the given date
     *
     * @param CarbonInterface $date
     *
     * @return RecurringEvent|null
     */
    public function getPrevOccurrence(CarbonInterface $date): RecurringEvent|null
    {
        $prevDate = $this->intervalStrategy->getPrevOccurrenceDate($date);

        if (is_null($prevDate)) {
            return null;
        }

        return $this->buildRecurringEvent($prevDate);
    }

    public function updateDetails(
        string $title,
        string $description,
        Coordinate|null $location,
        string|null $meetingLink = null,
        Address|null $address = null,
    ): void {
        $this->eventDetails = new EventDetails(
            $title,
            $description,
            $this->getEventDetails()->getStartTime(),
            $this->getEventDetails()->getEndTime(),
            $this->getEventDetails()->getTimeZone(),
            $location,
            $meetingLink,
            $address
        );
    }

    private function buildRecurringEvent(CarbonInterface $date): RecurringEvent
    {
        return new RecurringEvent(
            RecurringEventId::create($this->getId(), $this->getOfficeId(), $date),
            $this->getEventType(),
            $this->getEventDetails(),
            $this->participantIds,
            $this->intervalStrategy,
            $this->getOverrideOnDate($date)
        );
    }
}
