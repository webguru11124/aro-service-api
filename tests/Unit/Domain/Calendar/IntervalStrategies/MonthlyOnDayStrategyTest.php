<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Calendar\IntervalStrategies;

use App\Domain\Calendar\Enums\EndAfter;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\IntervalStrategies\MonthlyOnDayStrategy;
use App\Domain\Calendar\ValueObjects\EventEnd;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Tests\TestCase;

class MonthlyOnDayStrategyTest extends TestCase
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
        $strategy = new MonthlyOnDayStrategy($startDate, $eventEnd, $repeatEvery);

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
        yield [$startDate, $eventEndAfterDate, 1, Carbon::parse('2023-02-01'), true];
        yield [$startDate, $eventEndAfterDate, 1, Carbon::parse('2023-06-01'), true];
        yield [$startDate, $eventEndAfterDate, 1, Carbon::parse('2024-01-01'), false];
        yield [$startDate, $eventEndAfterDate, 1, $startDate->clone()->subMonth(), false];
        yield [$startDate, $eventEndAfterDate, 1, $startDate->clone()->subWeek(), false];
        yield [$startDate, $eventEndAfterDate, 1, $startDate->clone()->subDay(), false];
        yield [$startDate, $eventEndAfterDate, 1, $startDate->clone()->addWeek(), false];
        yield [$startDate, $eventEndAfterDate, 1, $startDate->clone()->addDays(27), false];

        // Repeat every N months
        yield [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addMonth(), false];
        yield [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addMonths(2), true];
        yield [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addMonths(3), false];

        yield [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addMonth(), false];
        yield [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addMonths(2), false];
        yield [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addMonths(3), true];
        yield [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addMonths(4), false];
        yield [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addMonths(5), false];
        yield [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addMonths(6), true];

        // End after occurrences
        yield [$startDate, $eventEndAfterOccurrences, 1, $startDate, true];
        yield [$startDate, $eventEndAfterOccurrences, 1, $startDate->clone()->addMonths($occurrences - 2), true];
        yield [$startDate, $eventEndAfterOccurrences, 1, $startDate->clone()->addMonths($occurrences - 1), true];
        yield [$startDate, $eventEndAfterOccurrences, 1, $startDate->clone()->addMonths($occurrences), false];
        yield [$startDate, $eventEndAfterOccurrences, 1, $startDate->clone()->subMonth(), false];
        yield [$startDate, $eventEndAfterOccurrences, 1, $startDate->clone()->addDays(30), false];

        // Repeat every N days and end after occurrences
        yield [$startDate, $eventEndAfterOccurrences, 2, $startDate->clone()->addMonths($occurrences * 2 - 2), true];
        yield [$startDate, $eventEndAfterOccurrences, 2, $startDate->clone()->addMonths($occurrences * 2 - 1), false];
        yield [$startDate, $eventEndAfterOccurrences, 2, $startDate->clone()->addMonths($occurrences * 2), false];
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
        $strategy = new MonthlyOnDayStrategy($startDate, $eventEnd, $repeatEvery);

        $this->assertEquals(
            $expectedDate?->toDateString(),
            $strategy->getNextOccurrenceDate($currentDate)?->toDateString()
        );
    }

    public static function nextOccurrenceDataProvider(): iterable
    {
        $startDate = Carbon::parse('2023-05-01');
        $endDate = Carbon::parse('2023-12-31');
        $eventEndAfterDate = new EventEnd(EndAfter::DATE, $endDate);
        $occurrences = 3;
        $eventEndAfterOccurrences = new EventEnd(EndAfter::OCCURRENCES, null, $occurrences);

        // End after date
        yield 'd_before_start_date' => [$startDate, $eventEndAfterDate, 1, $startDate->clone()->subWeek(), $startDate];
        yield 'd_inside_active_period' => [$startDate, $eventEndAfterDate, 1, $startDate, $startDate->clone()->addMonth()];
        yield 'd_at_the_end_of_active_period' => [$startDate, $eventEndAfterDate, 1, $endDate->clone()->subDay(), null];
        yield 'd_after_end_date' => [$startDate, $eventEndAfterDate, 1, $endDate, null];

        // Repeat every N months
        yield 'repeat_after_2_months' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addDay(), $startDate->clone()->addMonths(2)];
        yield 'repeat_after_2_months_1' => [$startDate, $eventEndAfterDate, 2, $startDate->clone(), $startDate->clone()->addMonths(2)];
        yield 'repeat_after_2_months_2' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addMonth(), $startDate->clone()->addMonths(2)];
        yield 'repeat_after_4_months_1' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addMonths(2), $startDate->clone()->addMonths(4)];
        yield 'repeat_after_4_months_2' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addMonths(3), $startDate->clone()->addMonths(4)];

        yield 'repeat_after_3_months_1' => [$startDate, $eventEndAfterDate, 3, $startDate->clone(), $startDate->clone()->addMonths(3)];
        yield 'repeat_after_3_months_2' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addMonth(), $startDate->clone()->addMonths(3)];
        yield 'repeat_after_3_months_3' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addMonths(2), $startDate->clone()->addMonths(3)];
        yield 'repeat_after_3_months_4' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addMonths(3), $startDate->clone()->addMonths(6)];
        yield 'repeat_after_3_months_5' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addMonths(4), $startDate->clone()->addMonths(6)];
        yield 'repeat_after_3_months_6' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addMonths(5), $startDate->clone()->addMonths(6)];

        // End after occurrences
        yield 'o_before_start_date' => [$startDate, $eventEndAfterOccurrences, 1, $startDate->clone()->subWeek(), $startDate];
        yield 'o_inside_active_period' => [$startDate, $eventEndAfterOccurrences, 1, $startDate, $startDate->clone()->addMonth()];
        yield 'o_at_the_end_of_active_period' => [
            $startDate,
            $eventEndAfterOccurrences,
            1,
            $startDate->clone()->addMonths($occurrences - 1)->subDay(),
            $startDate->clone()->addMonths($occurrences - 1),
        ];
        yield 'o_after_end_date' => [
            $startDate,
            $eventEndAfterOccurrences,
            1,
            $startDate->clone()->addMonths($occurrences - 1),
            null,
        ];
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
        $strategy = new MonthlyOnDayStrategy($startDate, $eventEnd, $repeatEvery);

        $this->assertEquals(
            $expectedDate?->toDateString(),
            $strategy->getPrevOccurrenceDate($currentDate)?->toDateString()
        );
    }

    public static function prevOccurrenceDataProvider(): iterable
    {
        $startDate = Carbon::parse('2023-05-01');
        $endDate = Carbon::parse('2023-12-01');
        $eventEndAfterDate = new EventEnd(EndAfter::DATE, $endDate);
        $occurrences = 3;
        $eventEndAfterOccurrences = new EventEnd(EndAfter::OCCURRENCES, null, $occurrences);

        // End after date
        yield 'd_before_start_date' => [$startDate, $eventEndAfterDate, 1, $startDate, null];
        yield 'd_inside_active_period' => [
            $startDate,
            $eventEndAfterDate,
            1,
            $endDate->clone()->subMonth(),
            $endDate->clone()->subMonths(2),
        ];
        yield 'd_at_the_end_of_active_period' => [
            $startDate,
            $eventEndAfterDate,
            1,
            $endDate->clone(),
            $endDate->clone()->subMonth(),
        ];
        yield 'd_after_end_date' => [$startDate, $eventEndAfterDate, 1, $endDate->clone()->addWeek(), $endDate->clone()];

        // Repeat every N months
        yield 'repeat_after_2_months_1' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addMonths(6), $startDate->clone()->addMonths(4)];
        yield 'repeat_after_2_months_2' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addMonths(5), $startDate->clone()->addMonths(4)];
        yield 'repeat_after_4_months_1' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addMonths(4), $startDate->clone()->addMonths(2)];
        yield 'repeat_after_4_months_2' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addMonths(3), $startDate->clone()->addMonths(2)];

        yield 'repeat_after_3_months_1' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addMonths(7), $startDate->clone()->addMonths(6)];
        yield 'repeat_after_3_months_2' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addMonths(6), $startDate->clone()->addMonths(3)];
        yield 'repeat_after_3_months_3' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addMonths(5), $startDate->clone()->addMonths(3)];
        yield 'repeat_after_3_months_4' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addMonths(4), $startDate->clone()->addMonths(3)];
        yield 'repeat_after_3_months_5' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addMonths(3), $startDate->clone()->addMonths(0)];
        yield 'repeat_after_3_months_6' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addMonths(2), $startDate->clone()->addMonths(0)];

        // End after occurrences
        yield 'o_before_start_date' => [$startDate, $eventEndAfterOccurrences, 1, $startDate, null];
        yield 'o_inside_active_period' => [
            $startDate,
            $eventEndAfterOccurrences,
            1,
            $startDate->clone()->addMonth(),
            $startDate,
        ];
        yield 'o_at_the_end_of_active_period' => [
            $startDate,
            $eventEndAfterOccurrences,
            1,
            $startDate->clone()->addMonths($occurrences - 1),
            $startDate->clone()->addMonths($occurrences - 2),
        ];
        yield 'o_after_end_date' => [
            $startDate,
            $eventEndAfterOccurrences,
            1,
            $startDate->clone()->addMonths($occurrences),
            $startDate->clone()->addMonths($occurrences - 1),
        ];
    }

    /**
     * @test
     */
    public function it_returns_expected_values_from_getters(): void
    {
        $startDate = Carbon::parse('2023-01-01');
        $endDate = Carbon::parse('2023-08-31');
        $eventEndAfterDate = new EventEnd(EndAfter::DATE, $endDate);
        $strategy = new MonthlyOnDayStrategy($startDate, $eventEndAfterDate);

        $this->assertEquals(ScheduleInterval::MONTHLY_ON_DAY, $strategy->getInterval());
    }
}
