<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Calendar\IntervalStrategies;

use App\Domain\Calendar\Enums\EndAfter;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\IntervalStrategies\YearlyStrategy;
use App\Domain\Calendar\ValueObjects\EventEnd;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Tests\TestCase;

class YearlyStrategyTest extends TestCase
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
        $strategy = new YearlyStrategy($startDate, $eventEnd, $repeatEvery);

        $this->assertEquals($expectedResult, $strategy->isScheduledOnDate($date));
    }

    public static function dataProvider(): iterable
    {
        $startDate = Carbon::parse('2023-01-01');
        $endDate = Carbon::parse('2030-12-31');
        $eventEndAfterDate = new EventEnd(EndAfter::DATE, $endDate);
        $occurrences = random_int(3, 10);
        $eventEndAfterOccurrences = new EventEnd(EndAfter::OCCURRENCES, null, $occurrences);

        // End after date
        yield [$startDate, $eventEndAfterDate, 1, $startDate, true];
        yield [$startDate, $eventEndAfterDate, 1, $startDate->clone()->subMonth(), false];
        yield [$startDate, $eventEndAfterDate, 1, $startDate->clone()->addMonth(), false];
        yield [$startDate, $eventEndAfterDate, 1, $startDate->clone()->addYear(), true];
        yield [$startDate, $eventEndAfterDate, 1, $startDate->clone()->addYears(8), false];

        // Repeat every N years
        yield [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addYear(), false];
        yield [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addYears(2), true];
        yield [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addYears(3), false];

        yield [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addYear(), false];
        yield [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addYears(2), false];
        yield [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addYears(3), true];
        yield [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addYears(4), false];
        yield [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addYears(5), false];
        yield [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addYears(6), true];

        // End after occurrences
        yield [$startDate, $eventEndAfterOccurrences, 1, $startDate, true];
        yield [$startDate, $eventEndAfterOccurrences, 1, $startDate->clone()->subMonth(), false];
        yield [$startDate, $eventEndAfterOccurrences, 1, $startDate->clone()->addMonth(), false];
        yield [$startDate, $eventEndAfterOccurrences, 1, $startDate->clone()->addYear(), true];
        yield [$startDate, $eventEndAfterOccurrences, 1, $startDate->clone()->addYears($occurrences - 1), true];
        yield [$startDate, $eventEndAfterOccurrences, 1, $startDate->clone()->addYears($occurrences), false];

        // Repeat every N days and end after occurrences
        yield [$startDate, $eventEndAfterOccurrences, 2, $startDate->clone()->addYears($occurrences * 2 - 2), true];
        yield [$startDate, $eventEndAfterOccurrences, 2, $startDate->clone()->addYears($occurrences * 2 - 1), false];
        yield [$startDate, $eventEndAfterOccurrences, 2, $startDate->clone()->addYears($occurrences * 2), false];
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
        $strategy = new YearlyStrategy($startDate, $eventEnd, $repeatEvery);

        $this->assertEquals(
            $expectedDate?->toDateString(),
            $strategy->getNextOccurrenceDate($currentDate)?->toDateString()
        );
    }

    public static function nextOccurrenceDataProvider(): iterable
    {
        $startDate = Carbon::parse('2023-01-01');
        $endDate = Carbon::parse('2030-12-31');
        $eventEndAfterDate = new EventEnd(EndAfter::DATE, $endDate);
        $occurrences = 3;
        $eventEndAfterOccurrences = new EventEnd(EndAfter::OCCURRENCES, null, $occurrences);

        // End after date
        yield 'd_before_start_date' => [$startDate, $eventEndAfterDate, 1, $startDate->clone()->subWeek(), $startDate];
        yield 'd_inside_active_period' => [$startDate, $eventEndAfterDate, 1, $startDate, $startDate->clone()->addYear()];
        yield 'd_at_the_end_of_active_period' => [$startDate, $eventEndAfterDate, 1, $endDate->clone()->subDay(), null];
        yield 'd_after_end_date' => [$startDate, $eventEndAfterDate, 1, $endDate, null];

        // Repeat every N years
        yield 'repeat_after_2_years' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addDay(), $startDate->clone()->addYears(2)];
        yield 'repeat_after_2_years_1' => [$startDate, $eventEndAfterDate, 2, $startDate->clone(), $startDate->clone()->addYears(2)];
        yield 'repeat_after_2_years_2' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addYear(), $startDate->clone()->addYears(2)];
        yield 'repeat_after_4_years_1' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addYears(2), $startDate->clone()->addYears(4)];
        yield 'repeat_after_4_years_2' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addYears(3), $startDate->clone()->addYears(4)];

        yield 'repeat_after_3_years_1' => [$startDate, $eventEndAfterDate, 3, $startDate->clone(), $startDate->clone()->addYears(3)];
        yield 'repeat_after_3_years_2' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addYear(), $startDate->clone()->addYears(3)];
        yield 'repeat_after_3_years_3' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addYears(2), $startDate->clone()->addYears(3)];
        yield 'repeat_after_3_years_4' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addYears(3), $startDate->clone()->addYears(6)];
        yield 'repeat_after_3_years_5' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addYears(4), $startDate->clone()->addYears(6)];
        yield 'repeat_after_3_years_6' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addYears(5), $startDate->clone()->addYears(6)];

        // End after occurrences
        yield 'o_before_start_date' => [$startDate, $eventEndAfterOccurrences, 1, $startDate->clone()->subWeek(), $startDate];
        yield 'o_inside_active_period' => [$startDate, $eventEndAfterOccurrences, 1, $startDate, $startDate->clone()->addYear()];
        yield 'o_at_the_end_of_active_period' => [
            $startDate,
            $eventEndAfterOccurrences,
            1,
            $startDate->clone()->addYears($occurrences - 2),
            $startDate->clone()->addYears($occurrences - 1),
        ];
        yield 'o_after_end_date' => [
            $startDate,
            $eventEndAfterOccurrences,
            1,
            $startDate->clone()->addYears($occurrences - 1),
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
        $strategy = new YearlyStrategy($startDate, $eventEnd, $repeatEvery);

        $this->assertEquals(
            $expectedDate?->toDateString(),
            $strategy->getPrevOccurrenceDate($currentDate)?->toDateString()
        );
    }

    public static function prevOccurrenceDataProvider(): iterable
    {
        $startDate = Carbon::parse('2023-01-01');
        $endDate = Carbon::parse('2030-12-31');
        $eventEndAfterDate = new EventEnd(EndAfter::DATE, $endDate);
        $occurrences = 3;
        $eventEndAfterOccurrences = new EventEnd(EndAfter::OCCURRENCES, null, $occurrences);

        // End after date
        yield 'd_before_start_date' => [$startDate, $eventEndAfterDate, 1, $startDate, null];
        yield 'd_inside_active_period' => [$startDate, $eventEndAfterDate, 1, $endDate->clone()->subYear(), $startDate->clone()->addYears(6)];
        yield 'd_at_the_end_of_active_period' => [$startDate, $eventEndAfterDate, 1, $endDate->clone()->subDay(), $startDate->clone()->addYears(7)];
        yield 'd_after_end_date' => [$startDate, $eventEndAfterDate, 1, $endDate->clone()->addWeek(), $startDate->clone()->addYears(7)];

        // Repeat every N years
        yield 'repeat_after_2_years_1' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addYears(6), $startDate->clone()->addYears(4)];
        yield 'repeat_after_2_years_2' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addYears(5), $startDate->clone()->addYears(4)];
        yield 'repeat_after_4_years_1' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addYears(4), $startDate->clone()->addYears(2)];
        yield 'repeat_after_4_years_2' => [$startDate, $eventEndAfterDate, 2, $startDate->clone()->addYears(3), $startDate->clone()->addYears(2)];

        yield 'repeat_after_3_years_1' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addYears(7), $startDate->clone()->addYears(6)];
        yield 'repeat_after_3_years_2' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addYears(6), $startDate->clone()->addYears(3)];
        yield 'repeat_after_3_years_3' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addYears(5), $startDate->clone()->addYears(3)];
        yield 'repeat_after_3_years_4' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addYears(4), $startDate->clone()->addYears(3)];
        yield 'repeat_after_3_years_5' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addYears(3), $startDate->clone()->addYears(0)];
        yield 'repeat_after_3_years_6' => [$startDate, $eventEndAfterDate, 3, $startDate->clone()->addYears(2), $startDate->clone()->addYears(0)];

        // End after occurrences
        yield 'o_before_start_date' => [$startDate, $eventEndAfterOccurrences, 1, $startDate, null];
        yield 'o_inside_active_period' => [
            $startDate,
            $eventEndAfterOccurrences,
            1,
            $startDate->clone()->addYear(),
            $startDate->clone(),
        ];
        yield 'o_at_the_end_of_active_period' => [
            $startDate,
            $eventEndAfterOccurrences,
            1,
            $startDate->clone()->addYears($occurrences - 1),
            $startDate->clone()->addYears($occurrences - 2),
        ];
        yield 'o_after_end_date' => [
            $startDate,
            $eventEndAfterOccurrences,
            1,
            $startDate->clone()->addYears($occurrences),
            $startDate->clone()->addYears($occurrences - 1),
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

        $strategy = new YearlyStrategy($startDate, $eventEndAfterDate);

        $this->assertEquals(ScheduleInterval::YEARLY, $strategy->getInterval());

        $this->assertNull($strategy->getOccurrence());
    }
}
