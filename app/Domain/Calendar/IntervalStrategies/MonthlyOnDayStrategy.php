<?php

declare(strict_types=1);

namespace App\Domain\Calendar\IntervalStrategies;

use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\ValueObjects\EventEnd;
use Carbon\CarbonInterface;

class MonthlyOnDayStrategy extends AbstractIntervalStrategy
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
        return ScheduleInterval::MONTHLY_ON_DAY;
    }

    /**
     * @param CarbonInterface $date
     *
     * @return bool
     */
    public function isScheduledOnDate(CarbonInterface $date): bool
    {
        $diffInMonths = $this->startDate->diffInMonths($date);
        $isOnMonth = $diffInMonths % $this->repeatEvery === 0;

        return $this->isActiveOnDate($date)
            && $isOnMonth
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
        $nextDate = $date->clone()->day($this->startDate->day);

        if ($date->month === $this->startDate->month || $date->greaterThanOrEqualTo($nextDate)) {
            $nextDate->addMonth();
        }

        if ($this->repeatEvery > 1) {
            $monthsDiff = $this->startDate->diffInMonths($nextDate) % $this->repeatEvery;

            if ($monthsDiff > 0) {
                $nextDate = $nextDate->addMonths($this->repeatEvery - $monthsDiff);
            }
        }

        return $this->isScheduledOnDate($nextDate) ? $nextDate : $this->getFirstOccurrenceDate($date);
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
        $prevDate = $date->clone()->day($this->startDate->day);

        if ($date->month === $this->startDate->month || $date->lessThanOrEqualTo($prevDate)) {
            $prevDate->subMonth();
        }

        if ($this->repeatEvery > 1) {
            $monthsDiff = $this->startDate->diffInMonths($prevDate) % $this->repeatEvery;

            if ($monthsDiff > 0) {
                $prevDate = $prevDate->subMonths($monthsDiff);
            }
        }

        return $this->isScheduledOnDate($prevDate) ? $prevDate : $this->getLastOccurrenceDate($date);
    }

    protected function getLastOccurrenceDate(CarbonInterface $date): CarbonInterface|null
    {
        $thresholdDate = $this->getThresholdDate();

        return is_null($thresholdDate) || $date->lessThan($thresholdDate)
            ? null
            : $this->getPrevOccurrenceDate($thresholdDate);
    }

    protected function getEndAfterOccurrencesEndDate(): CarbonInterface|null
    {
        $occurrences = $this->getMaxOccurrences();
        if ($occurrences === null) {
            return null;
        }

        return $this->startDate->clone()->addMonths($occurrences * $this->repeatEvery - 1);
    }
}
