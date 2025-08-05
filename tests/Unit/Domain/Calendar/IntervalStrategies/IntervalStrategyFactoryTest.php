<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Calendar\IntervalStrategies;

use App\Domain\Calendar\Enums\EndAfter;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\Enums\WeekDay;
use App\Domain\Calendar\IntervalStrategies\DailyStrategy;
use App\Domain\Calendar\IntervalStrategies\IntervalStrategyFactory;
use App\Domain\Calendar\IntervalStrategies\MonthlyOnDayStrategy;
use App\Domain\Calendar\IntervalStrategies\MonthlyStrategy;
use App\Domain\Calendar\IntervalStrategies\OnceStrategy;
use App\Domain\Calendar\IntervalStrategies\WeeklyStrategy;
use App\Domain\Calendar\IntervalStrategies\YearlyStrategy;
use App\Domain\Calendar\ValueObjects\EventEnd;
use App\Domain\Calendar\ValueObjects\WeeklyOccurrence;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Tests\TestCase;

class IntervalStrategyFactoryTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider dataProvider
     */
    public function it_returns_proper_strategy(
        CarbonInterface $startDate,
        EventEnd $eventEnd,
        ScheduleInterval $interval,
        int $repeatEvery,
        WeeklyOccurrence|null $occurrence,
        int|null $weekNumber,
        string $expectedClass
    ): void {
        $factory = new IntervalStrategyFactory();

        $strategy = $factory->getIntervalStrategy($startDate, $eventEnd, $interval, $repeatEvery, $occurrence, $weekNumber);

        $this->assertEquals($expectedClass, $strategy::class);
    }

    public static function dataProvider(): iterable
    {
        $startDay = Carbon::tomorrow();
        $endDay = $startDay->clone()->addYear();
        $eventEnd = new EventEnd(EndAfter::DATE, $endDay);
        $occurrence = new WeeklyOccurrence(collect([WeekDay::MONDAY]));
        $weekNumber = 1;

        yield [$startDay, $eventEnd, ScheduleInterval::ONCE, 1, null, null, OnceStrategy::class];
        yield [$startDay, $eventEnd, ScheduleInterval::DAILY, 1, null, null, DailyStrategy::class];
        yield [$startDay, $eventEnd, ScheduleInterval::WEEKLY, 1, $occurrence, null, WeeklyStrategy::class];
        yield [$startDay, $eventEnd, ScheduleInterval::MONTHLY, 1, $occurrence, $weekNumber, MonthlyStrategy::class];
        yield [$startDay, $eventEnd, ScheduleInterval::MONTHLY_ON_DAY, 1, null, null, MonthlyOnDayStrategy::class];
        yield [$startDay, $eventEnd, ScheduleInterval::YEARLY, 1, null, null, YearlyStrategy::class];
    }
}
