<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Calendar\IntervalStrategies;

use App\Domain\Calendar\Enums\EndAfter;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\IntervalStrategies\DailyStrategy;
use App\Domain\Calendar\ValueObjects\EventEnd;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Tests\TestCase;

class DailyStrategyTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider dataProvider
     *
     * ::isScheduledOnDate
     */
    public function it_determines_if_scheduled_on_date(
        CarbonInterface $startDate,
        EventEnd $eventEnd,
        int $repeatEvery,
        CarbonInterface $date,
        bool $expectedResult
    ): void {
        $strategy = new DailyStrategy($startDate, $eventEnd, $repeatEvery);

        $this->assertEquals($expectedResult, $strategy->isScheduledOnDate($date));
    }

    public static function dataProvider(): iterable
    {
        $startDate = Carbon::parse('2023-01-01');
        $endDate = Carbon::parse('2023-12-31');
        $eventEndAfterDate = new EventEnd(EndAfter::DATE, $endDate);
        $occurrences = 3;
        $eventEndAfterOccurrences = new EventEnd(EndAfter::OCCURRENCES, null, $occurrences);

        // End after date
        yield [$startDate, $eventEndAfterDate, 1, $startDate, true];
        yield [$startDate, $eventEndAfterDate, 1, $endDate, true];

        yield [$startDate, $eventEndAfterDate, 1, Carbon::parse('2023-02-01'), true];
        yield [$startDate, $eventEndAfterDate, 1, Carbon::parse('2023-06-01'), true];
        yield [$startDate, $eventEndAfterDate, 1, $startDate->clone()->subMonth(), false];
        yield [$startDate, $eventEndAfterDate, 1, $startDate->clone()->subWeek(), false];
        yield [$startDate, $eventEndAfterDate, 1, $startDate->clone()->subDay(), false];

        yield [$startDate, $eventEndAfterDate, 1, $startDate->clone()->addDay(), true];
        yield [$startDate, $eventEndAfterDate, 1, $startDate->clone()->addWeek(), true];
        yield [$startDate, $eventEndAfterDate, 1, $startDate->clone()->addMonth(), true];

        yield [$startDate, $eventEndAfterDate, 1, $endDate->clone()->addDay(), false];
        yield [$startDate, $eventEndAfterDate, 1, $endDate->clone()->addWeek(), false];
        yield [$startDate, $eventEndAfterDate, 1, $endDate->clone()->addMonth(), false];

        // Repeat every N days
        yield [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addDay(), false];
        yield [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addDays(2), true];
        yield [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addDays(3), false];

        yield [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addDay(), false];
        yield [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addDays(2), false];
        yield [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addDays(3), true];
        yield [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addDays(4), false];
        yield [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addDays(5), false];
        yield [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addDays(6), true];

        // End after occurrences
        yield [$startDate, $eventEndAfterOccurrences, 1, $startDate->clone()->subDay(), false];
        yield [$startDate, $eventEndAfterOccurrences, 1, $startDate->clone(), true];
        yield [$startDate, $eventEndAfterOccurrences, 1, $startDate->clone()->addDay(), true];
        yield [$startDate, $eventEndAfterOccurrences, 1, $startDate->clone()->addDays($occurrences - 1), true];
        yield [$startDate, $eventEndAfterOccurrences, 1, $startDate->clone()->addDays($occurrences), false];

        // Repeat every N days and end after occurrences
        yield [$startDate, $eventEndAfterOccurrences, 2, $startDate->clone()->addDays($occurrences * 2 - 2), true];
        yield [$startDate, $eventEndAfterOccurrences, 2, $startDate->clone()->addDays($occurrences * 2 - 1), false];
        yield [$startDate, $eventEndAfterOccurrences, 2, $startDate->clone()->addDays($occurrences * 2), false];
    }

    /**
     * @test
     *
     * @dataProvider nextOccurrenceDataProvider
     *
     * ::getNextOccurrenceDate
     */
    public function it_returns_next_occurrence_date(
        CarbonInterface $startDate,
        EventEnd $eventEnd,
        int $repeatEvery,
        CarbonInterface $currentDate,
        CarbonInterface|null $expectedDate
    ): void {
        $strategy = new DailyStrategy($startDate, $eventEnd, $repeatEvery);

        $this->assertEquals(
            $expectedDate?->toDateString(),
            $strategy->getNextOccurrenceDate($currentDate)?->toDateString()
        );
    }

    public static function nextOccurrenceDataProvider(): iterable
    {
        $startDate = Carbon::parse('2023-05-01');
        $endDate = Carbon::parse('2023-08-31');
        $eventEndAfterDate = new EventEnd(EndAfter::DATE, $endDate);
        $occurrences = random_int(3, 10);
        $eventEndAfterOccurrences = new EventEnd(EndAfter::OCCURRENCES, null, $occurrences);

        // End after date
        yield 'd_before_start_date' => [$startDate, $eventEndAfterDate, 1, $startDate->clone()->subWeek(), $startDate];
        yield 'd_inside_active_period' => [$startDate, $eventEndAfterDate, 1, $startDate, $startDate->clone()->addDay()];
        yield 'd_after_end_date' => [$startDate, $eventEndAfterDate, 1, $endDate, null];

        // Repeat every N days
        yield 'repeat_after_2_days_1' => [$startDate, $eventEndAfterDate, 2, $startDate->clone(), $startDate->clone()->addDays(2)];
        yield 'repeat_after_2_days_2' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addDay(), $startDate->clone()->addDays(2)];
        yield 'repeat_after_4_days_1' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addDays(2), $startDate->clone()->addDays(4)];
        yield 'repeat_after_4_days_2' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addDays(3), $startDate->clone()->addDays(4)];

        yield 'repeat_after_3_days_1' => [$startDate, $eventEndAfterDate, 3, $startDate->clone(), $startDate->clone()->addDays(3)];
        yield 'repeat_after_3_days_2' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addDay(), $startDate->clone()->addDays(3)];
        yield 'repeat_after_3_days_3' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addDays(2), $startDate->clone()->addDays(3)];
        yield 'repeat_after_3_days_4' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addDays(3), $startDate->clone()->addDays(6)];
        yield 'repeat_after_3_days_5' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addDays(4), $startDate->clone()->addDays(6)];
        yield 'repeat_after_3_days_6' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addDays(5), $startDate->clone()->addDays(6)];

        // End after occurrences
        yield 'o_before_start_date' => [$startDate, $eventEndAfterOccurrences, 1, $startDate->clone()->subWeek(), $startDate];
        yield 'o_inside_active_period' => [$startDate, $eventEndAfterOccurrences, 1, $startDate, $startDate->clone()->addDay()];
        yield 'o_after_end_date' => [$startDate, $eventEndAfterOccurrences, 1, $startDate->clone()->addDays($occurrences - 1), null];
    }

    /**
     * @test
     *
     * @dataProvider prevOccurrenceDataProvider
     *
     * ::getPrevOccurrenceDate
     */
    public function it_returns_prev_occurrence_date(
        CarbonInterface $startDate,
        EventEnd $eventEnd,
        int $repeatEvery,
        CarbonInterface $currentDate,
        CarbonInterface|null $expectedDate
    ): void {
        $strategy = new DailyStrategy($startDate, $eventEnd, $repeatEvery);

        $this->assertEquals($expectedDate?->toDateString(), $strategy->getPrevOccurrenceDate($currentDate)?->toDateString());
    }

    public static function prevOccurrenceDataProvider(): iterable
    {
        $startDate = Carbon::parse('2023-05-01');
        $endDate = Carbon::parse('2023-08-31');
        $eventEndAfterDate = new EventEnd(EndAfter::DATE, $endDate);
        $occurrences = random_int(3, 10);
        $eventEndAfterOccurrences = new EventEnd(EndAfter::OCCURRENCES, null, $occurrences);

        // End after date
        yield 'd_before_start_date' => [$startDate, $eventEndAfterDate, 1, $startDate, null];
        yield 'd_inside_active_period' => [$startDate, $eventEndAfterDate, 1, $endDate, $endDate->clone()->subDay()];
        yield 'd_after_end_date' => [$startDate, $eventEndAfterDate, 1, $endDate->clone()->addWeek(), $endDate->clone()];

        // Repeat every N days
        yield 'repeat_after_2_days_1' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addDays(10), $startDate->clone()->addDays(8)];
        yield 'repeat_after_2_days_2' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addDays(9), $startDate->clone()->addDays(8)];
        yield 'repeat_after_4_days_1' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addDays(8), $startDate->clone()->addDays(6)];
        yield 'repeat_after_4_days_2' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addDays(7), $startDate->clone()->addDays(6)];

        yield 'repeat_after_3_days_1' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addDays(10), $startDate->clone()->addDays(9)];
        yield 'repeat_after_3_days_2' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addDays(9), $startDate->clone()->addDays(6)];
        yield 'repeat_after_3_days_3' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addDays(8), $startDate->clone()->addDays(6)];
        yield 'repeat_after_3_days_4' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addDays(7), $startDate->clone()->addDays(6)];
        yield 'repeat_after_3_days_5' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addDays(6), $startDate->clone()->addDays(3)];

        // End after occurrences
        yield 'o_before_start_date' => [$startDate, $eventEndAfterOccurrences, 1, $startDate, null];
        yield 'o_inside_active_period' => [
            $startDate,
            $eventEndAfterOccurrences,
            1,
            $startDate->clone()->addDays($occurrences - 1),
            $startDate->clone()->addDays($occurrences - 2),
        ];
        yield 'o_after_end_date' => [
            $startDate,
            $eventEndAfterOccurrences,
            1,
            $startDate->clone()->addDays($occurrences)->addWeek(),
            $startDate->clone()->addDays($occurrences - 1),
        ];
    }

    /**
     * @test
     */
    public function it_returns_expected_values_from_getters(): void
    {
        $startDate = Carbon::parse('2023-05-01');
        $endDate = Carbon::parse('2023-08-31');
        $eventEndAfterDate = new EventEnd(EndAfter::DATE, $endDate);

        $strategy = new DailyStrategy($startDate, $eventEndAfterDate);

        $this->assertEquals(ScheduleInterval::DAILY, $strategy->getInterval());

        $this->assertNull($strategy->getOccurrence());
    }
}
