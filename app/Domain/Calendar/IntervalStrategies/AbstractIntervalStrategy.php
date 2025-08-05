<?php

declare(strict_types=1);

namespace App\Domain\Calendar\IntervalStrategies;

use App\Domain\Calendar\Enums\EndAfter;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\ValueObjects\EventEnd;
use App\Domain\Calendar\ValueObjects\WeeklyOccurrence;
use Carbon\CarbonInterface;

abstract class AbstractIntervalStrategy
{
    public function __construct(
        protected readonly CarbonInterface $startDate,
        protected readonly EventEnd $eventEnd,
        protected int $repeatEvery = 1,
        protected WeeklyOccurrence|null $occurrence = null
    ) {
    }

    /**
     * @return CarbonInterface
     */
    public function getStartDate(): CarbonInterface
    {
        return $this->startDate;
    }

    /**
     * @return CarbonInterface|null
     */
    public function getEndDate(): CarbonInterface|null
    {
        return $this->eventEnd->getDate();
    }

    /**
     * @return int|null
     */
    public function getMaxOccurrences(): int|null
    {
        return $this->eventEnd->getOccurrences();
    }

    /**
     * @return EndAfter
     */
    public function getEndAfter(): EndAfter
    {
        return $this->eventEnd->getEndAfter();
    }

    /**
     * @return int
     */
    public function getRepeatEvery(): int
    {
        return $this->repeatEvery;
    }

    /**
     * @param CarbonInterface $date
     *
     * @return bool
     */
    protected function isActiveOnDate(CarbonInterface $date): bool
    {
        return $this->getStartDate() <= $date
            && ($this->getThresholdDate() === null || $this->getThresholdDate() >= $date);
    }

    /**
     * @return WeeklyOccurrence|null
     */
    public function getOccurrence(): WeeklyOccurrence|null
    {
        return $this->occurrence;
    }

    abstract public function getInterval(): ScheduleInterval;

    /**
     * Returns true if event is scheduled on the date
     *
     * @param CarbonInterface $date
     *
     * @return bool
     */
    abstract public function isScheduledOnDate(CarbonInterface $date): bool;

    /**
     * Returns date of the next closest occurrence
     *
     * @param CarbonInterface $date
     *
     * @return CarbonInterface|null
     */
    abstract public function getNextOccurrenceDate(CarbonInterface $date): CarbonInterface|null;

    /**
     * Returns date of the previous closest occurrence
     *
     * @param CarbonInterface $date
     *
     * @return CarbonInterface|null
     */
    abstract public function getPrevOccurrenceDate(CarbonInterface $date): CarbonInterface|null;

    /**
     * Returns date of the first occurrence after the given date
     *
     * @param CarbonInterface $date
     *
     * @return CarbonInterface|null
     */
    protected function getFirstOccurrenceDate(CarbonInterface $date): CarbonInterface|null
    {
        return $date->greaterThan($this->startDate)
            ? null
            : $this->startDate;
    }

    /**
     * Returns date of the last occurrence after the given date
     *
     * @param CarbonInterface $date
     *
     * @return CarbonInterface|null
     */
    protected function getLastOccurrenceDate(CarbonInterface $date): CarbonInterface|null
    {
        $thresholdDate = $this->getThresholdDate();

        return is_null($thresholdDate) || $date->lessThan($thresholdDate)
            ? null
            : $this->getPrevOccurrenceDate($thresholdDate->clone()->addDay());
    }

    protected function getThresholdDate(): CarbonInterface|null
    {
        if ($this->eventEnd->isDate()) {
            return $this->getEndDate();
        }

        if ($this->eventEnd->isNever()) {
            return null;
        }

        return $this->getEndAfterOccurrencesEndDate();
    }

    abstract protected function getEndAfterOccurrencesEndDate(): CarbonInterface|null;
}
