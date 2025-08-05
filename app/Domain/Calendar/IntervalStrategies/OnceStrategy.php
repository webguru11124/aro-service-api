<?php

declare(strict_types=1);

namespace App\Domain\Calendar\IntervalStrategies;

use App\Domain\Calendar\Enums\EndAfter;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\ValueObjects\EventEnd;
use Carbon\CarbonInterface;

class OnceStrategy extends AbstractIntervalStrategy
{
    public function __construct(
        CarbonInterface $startDate
    ) {
        parent::__construct($startDate, new EventEnd(EndAfter::NEVER));
    }

    /**
     * @return ScheduleInterval
     */
    public function getInterval(): ScheduleInterval
    {
        return ScheduleInterval::ONCE;
    }

    /**
     * @return null
     */
    public function getOccurrence(): null
    {
        return null;
    }

    /**
     * @param CarbonInterface $date
     *
     * @return bool
     */
    public function isScheduledOnDate(CarbonInterface $date): bool
    {
        return $date->toDateString() === $this->startDate->toDateString();
    }

    /**
     * Returns date of the next closest occurrence
     *
     * @param CarbonInterface $date
     *
     * @return CarbonInterface|null
     */
    public function getNextOccurrenceDate(CarbonInterface $date): CarbonInterface|null
    {
        return $this->getFirstOccurrenceDate($date);
    }

    /**
     * Returns date of the previous closest occurrence
     *
     * @param CarbonInterface $date
     *
     * @return CarbonInterface|null
     */
    public function getPrevOccurrenceDate(CarbonInterface $date): CarbonInterface|null
    {
        return $this->getLastOccurrenceDate($date);
    }

    protected function getLastOccurrenceDate(CarbonInterface $date): CarbonInterface|null
    {
        return $date->lessThan($this->startDate)
            ? null
            : $this->startDate;
    }

    protected function getEndAfterOccurrencesEndDate(): CarbonInterface|null
    {
        return $this->startDate->clone();
    }
}
