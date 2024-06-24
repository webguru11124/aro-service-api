<?php

declare(strict_types=1);

namespace App\Domain\Calendar\IntervalStrategies;

use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\ValueObjects\EventEnd;
use App\Domain\Calendar\ValueObjects\WeeklyOccurrence;
use Carbon\CarbonInterface;

class IntervalStrategyFactory
{
    /**
     * @param CarbonInterface $startDate
     * @param EventEnd $eventEnd
     * @param ScheduleInterval $interval
     * @param int $repeatEvery
     * @param WeeklyOccurrence|null $occurrence
     * @param int|null $weekNumber
     *
     * @return AbstractIntervalStrategy
     */
    public function getIntervalStrategy(
        CarbonInterface $startDate,
        EventEnd $eventEnd,
        ScheduleInterval $interval,
        int $repeatEvery = 1,
        WeeklyOccurrence|null $occurrence = null,
        int|null $weekNumber = null,
    ): AbstractIntervalStrategy {
        return match ($interval) {
            ScheduleInterval::ONCE => new OnceStrategy($startDate),
            ScheduleInterval::DAILY => new DailyStrategy($startDate, $eventEnd, $repeatEvery),
            ScheduleInterval::WEEKLY => new WeeklyStrategy($startDate, $eventEnd, $repeatEvery, $occurrence),
            ScheduleInterval::MONTHLY => new MonthlyStrategy($startDate, $eventEnd, $weekNumber, $repeatEvery),
            ScheduleInterval::MONTHLY_ON_DAY => new MonthlyOnDayStrategy($startDate, $eventEnd, $repeatEvery),
            ScheduleInterval::YEARLY => new YearlyStrategy($startDate, $eventEnd, $repeatEvery),
        };
    }
}
