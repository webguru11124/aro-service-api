<?php

declare(strict_types=1);

namespace App\Domain\Calendar\IntervalStrategies;

use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\ValueObjects\EventEnd;
use Carbon\CarbonInterface;

class YearlyStrategy extends AbstractIntervalStrategy
{
    public function __construct(
        CarbonInterface $startDate,
        EventEnd $eventEnd,
        int $repeatEvery = 1,
    ) {
        parent::__construct($startDate, $eventEnd, $repeatEvery);
    }

    /**
     * @return ScheduleInterval
     */
    public function getInterval(): ScheduleInterval
    {
        return ScheduleInterval::YEARLY;
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
        $diffInYears = $this->startDate->diffInYears($date);
        $isOnYear = $diffInYears % $this->repeatEvery === 0;

        return $this->isActiveOnDate($date)
            && $isOnYear
            && $date->month === $this->getStartDate()->month
            && $date->day === $this->getStartDate()->day;
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
        $nextDate = $this->startDate->clone()->year($date->year);

        if ($date->year === $this->startDate->year || $date->greaterThanOrEqualTo($nextDate)) {
            $nextDate->addYear();
        }

        if ($this->repeatEvery > 1) {
            $yearsDiff = $this->startDate->diffInYears($nextDate) % $this->repeatEvery;

            if ($yearsDiff > 0) {
                $nextDate = $nextDate->addYears($this->repeatEvery - $yearsDiff);
            }
        }

        return $this->isActiveOnDate($nextDate) ? $nextDate : $this->getFirstOccurrenceDate($date);
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
        $prevDate = $this->startDate->clone()->year($date->year);

        if ($date->year === $this->startDate->year || $date->lessThanOrEqualTo($prevDate)) {
            $prevDate->subYear();
        }

        if ($this->repeatEvery > 1) {
            $yearsDiff = $this->startDate->diffInYears($prevDate) % $this->repeatEvery;

            if ($yearsDiff > 0) {
                $prevDate = $prevDate->subYears($yearsDiff);
            }
        }

        return $this->isActiveOnDate($prevDate) ? $prevDate : $this->getLastOccurrenceDate($date);
    }

    protected function getEndAfterOccurrencesEndDate(): CarbonInterface|null
    {
        $occurrences = $this->getMaxOccurrences();
        if ($occurrences === null) {
            return null;
        }

        return $this->startDate->clone()->addYears($occurrences * $this->repeatEvery - 1);
    }
}
