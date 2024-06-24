<?php

declare(strict_types=1);

namespace App\Domain\Calendar\IntervalStrategies;

use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\Enums\WeekDay;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class WeeklyStrategy extends AbstractIntervalStrategy
{
    use Weekly;

    /**
     * @return ScheduleInterval
     */
    public function getInterval(): ScheduleInterval
    {
        return ScheduleInterval::WEEKLY;
    }

    /**
     * @param CarbonInterface $date
     *
     * @return bool
     */
    public function isScheduledOnDate(CarbonInterface $date): bool
    {
        if (!$this->isActiveOnDate($date)) {
            return false;
        }

        $diffInWeeks = $this->startDate->diffInWeeks($date);
        $isOnWeek = $diffInWeeks % $this->repeatEvery === 0;

        if (!$isOnWeek) {
            return false;
        }

        return $this->getOccurrence()->isOn($date->dayName);
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
        $nextDate = $date->clone();

        for ($d = 0; $d < 7; $d++) {
            $nextDate->addDay();

            if ($nextDate->greaterThan($this->getThresholdDate())) {
                return null;
            }

            if ($this->repeatEvery > 1 && $this->getOccurrence()->isOn($nextDate->dayName)) {
                $weeksDiff = $this->startDate->diffInWeeks($nextDate) % $this->repeatEvery;

                if ($weeksDiff > 0) {
                    $nextDate = $nextDate->addWeeks($this->repeatEvery - $weeksDiff);
                }
            }

            if ($this->isScheduledOnDate($nextDate)) {
                return $nextDate;
            }
        }

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
        $prevDate = $date->clone();

        for ($d = 0; $d < 7; $d++) {
            $prevDate->subDay();

            if ($prevDate->lessThan($this->startDate)) {
                return null;
            }

            if ($this->repeatEvery > 1 && $this->getOccurrence()->isOn($prevDate->dayName)) {
                $weeksDiff = $this->startDate->diffInWeeks($prevDate) % $this->repeatEvery;

                if ($weeksDiff > 0) {
                    $prevDate = $prevDate->subWeeks($weeksDiff);
                }
            }

            if ($this->isScheduledOnDate($prevDate)) {
                return $prevDate;
            }
        }

        return $this->getLastOccurrenceDate($date);
    }

    protected function getEndAfterOccurrencesEndDate(): CarbonInterface|null
    {
        $occurrences = $this->getMaxOccurrences();

        if ($occurrences === null) {
            return null;
        }

        $occurrences *= $this->repeatEvery;

        $weekDays = $this->occurrence->getOrderedWeekDays()->all();
        $occurrencesPerWeek = count($weekDays);
        $fullWeeks = (int) floor($occurrences / $occurrencesPerWeek);

        $firstEventDate = $this->getFirstEventDate();
        $endDate = $firstEventDate->clone()->addWeeks($fullWeeks);

        $occurrencesLeft = $occurrences - ($occurrencesPerWeek * $fullWeeks);

        if ($occurrencesLeft === 0) {
            return $endDate->subDay();
        }

        $endDateDay = WeekDay::from(strtolower($endDate->dayName));

        $dayPosition = array_search($endDateDay, $weekDays);
        $reorderedWeekDays = array_slice($weekDays, $dayPosition) + array_slice($weekDays, 0, $dayPosition);
        $lastWeekDays = array_slice($reorderedWeekDays, 0, $occurrencesLeft);

        /** @var WeekDay $lastWeekDay */
        $lastWeekDay = array_pop($lastWeekDays);
        $lastWeekDay = $lastWeekDay->name;

        return $endDate->subDay()->next(constant(Carbon::class . '::' . $lastWeekDay));
    }
}
