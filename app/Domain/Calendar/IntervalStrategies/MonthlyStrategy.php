<?php

declare(strict_types=1);

namespace App\Domain\Calendar\IntervalStrategies;

use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\Enums\WeekDay;
use App\Domain\Calendar\Enums\WeekNumInMonth;
use App\Domain\Calendar\ValueObjects\EventEnd;
use App\Domain\Calendar\ValueObjects\WeeklyOccurrence;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MonthlyStrategy extends AbstractIntervalStrategy
{
    public function __construct(
        CarbonInterface $startDate,
        EventEnd $eventEnd,
        int|null $weekNumber = null,
        int $repeatEvery = 1,
    ) {
        $weekNumberInMonth = $weekNumber === -1 ? null : ($weekNumber ?? $this->getNthWeekdayOfMonth($startDate));

        parent::__construct(
            $startDate,
            $eventEnd,
            $repeatEvery,
            new WeeklyOccurrence(
                collect([WeekDay::tryFrom(Str::lower($startDate->dayName))]),
                $weekNumberInMonth ? WeekNumInMonth::tryFrom($weekNumberInMonth) : null,
            ),
        );
    }

    /**
     * @return ScheduleInterval
     */
    public function getInterval(): ScheduleInterval
    {
        return ScheduleInterval::MONTHLY;
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

        $occurrenceInMonth = $this->getOccurrencesInMonth($date);

        if ($occurrenceInMonth->isEmpty()) {
            return false;
        }

        return $occurrenceInMonth->contains($date);
    }

    private function getNthWeekdayOfMonth(CarbonInterface $date): int
    {
        $dayOfMonth = $date->day;

        return (int) ceil($dayOfMonth / 7);
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
        $thresholdDate = $this->getThresholdDate();

        if ($thresholdDate && $date->gt($thresholdDate)) {
            return null;
        }

        if ($date->lt($this->startDate)) {
            return $this->startDate;
        }

        $searchDate = $date->copy()->startOfMonth();

        while (!$thresholdDate || $searchDate->lte($thresholdDate)) {
            if ($this->isSearchDateSameMonthAsStartDate($searchDate)) {
                $searchDate->addMonth();

                continue;
            }

            $occurrences = $this->getOccurrencesInMonth($searchDate);

            if ($occurrences->isNotEmpty()) {
                $nextDate = $occurrences->first(fn (CarbonInterface $eventDate) => $eventDate->gt($date));

                if ($nextDate) {
                    return $nextDate;
                }
            }

            $searchDate->addMonth();
        }

        return null;
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
        if ($date->lte($this->startDate)) {
            return null;
        }

        $searchDate = $date->copy()->endOfMonth();

        while ($searchDate->gte($this->startDate)) {
            if ($this->isSearchDateSameMonthAsStartDate($searchDate)) {
                return $this->startDate;
            }

            $occurrences = $this->getOccurrencesInMonth($searchDate);

            if ($occurrences->isNotEmpty()) {
                $prevDate = $occurrences->last(fn (CarbonInterface $eventDate) => $eventDate->lt($date));

                if ($prevDate) {
                    return $prevDate;
                }
            }

            $searchDate->subMonth();
        }

        return null;
    }

    private function isSearchDateSameMonthAsStartDate(CarbonInterface $searchDate): bool
    {
        return $searchDate->month === $this->startDate->month && $searchDate->year === $this->startDate->year;
    }

    /**
     * @param CarbonInterface $date
     *
     * @return Collection<CarbonInterface>
     */
    private function getOccurrencesInMonth(CarbonInterface $date): Collection
    {
        $diffInMonths = $this->startDate->diffInMonths($date);
        $isOnMonth = $diffInMonths % $this->repeatEvery === 0;

        if (!$isOnMonth) {
            return new Collection();
        }

        $weekNumberOfMonth = $this->getOccurrence()->weekNumInMonth?->value;

        return $this->getOccurrence()->weekDays->map(
            function (WeekDay $weekDay) use ($date, $weekNumberOfMonth) {
                $dayNumberInWeek = constant(Carbon::class . '::' . $weekDay->name);

                $eventDayInMonth = $weekNumberOfMonth === null
                    ? $date->clone()->lastOfMonth($dayNumberInWeek)
                    : $date->clone()->nthOfMonth($weekNumberOfMonth, $dayNumberInWeek);

                if ($eventDayInMonth === false) {
                    $eventDayInMonth = $date->clone()->lastOfMonth($dayNumberInWeek);
                    if (!$this->isActiveOnDate($eventDayInMonth)) {
                        return null;
                    }
                }

                return $this->isActiveOnDate($eventDayInMonth) ? $eventDayInMonth : null;
            }
        )->filter();
    }

    protected function getEndAfterOccurrencesEndDate(): CarbonInterface|null
    {
        $occurrences = $this->getMaxOccurrences();
        if ($occurrences === null) {
            return null;
        }

        $weeklyOccurrence = $this->getOccurrence();

        /** @var WeekDay $weekDay */
        $weekDay = $weeklyOccurrence->weekDays->first();
        $weekNumber = $weeklyOccurrence->weekNumInMonth;
        $dayInWeek = constant(Carbon::class . '::' . $weekDay->name);

        $dateAfterOccurrences = $this->startDate
            ->clone()
            ->addMonths($occurrences * $this->repeatEvery - 1);

        if (!$weekNumber) {
            return $dateAfterOccurrences->lastOfMonth($dayInWeek);
        }

        return $dateAfterOccurrences->nthOfMonth($weekNumber->value, $dayInWeek);
    }
}
