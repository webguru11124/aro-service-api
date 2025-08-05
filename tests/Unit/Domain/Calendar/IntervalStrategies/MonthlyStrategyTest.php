<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Calendar\IntervalStrategies;

use App\Domain\Calendar\Enums\EndAfter;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\IntervalStrategies\MonthlyStrategy;
use App\Domain\Calendar\ValueObjects\EventEnd;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Tests\TestCase;

class MonthlyStrategyTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider dataProvider
     */
    public function it_determines_if_scheduled_on_date(
        CarbonInterface $startDate,
        EventEnd $eventEnd,
        int $repeatEvery,
        int|null $weekNumber,
        CarbonInterface $date,
        bool $expectedResult
    ): void {
        $strategy = new MonthlyStrategy($startDate, $eventEnd, $weekNumber, $repeatEvery);

        $this->assertEquals($expectedResult, $strategy->isScheduledOnDate($date));
    }

    public static function dataProvider(): iterable
    {
        $startDate = Carbon::parse('2023-01-02');
        $endDate = Carbon::parse('2023-12-31');
        $lastEventDay = Carbon::parse('2023-12-18');
        $eventEndAfterDate = new EventEnd(EndAfter::DATE, $endDate);
        $occurrences = 12;
        $weekNumber = 3;
        $eventEndAfterOccurrences = new EventEnd(EndAfter::OCCURRENCES, null, $occurrences);

        // End after date
        $eventDate = Carbon::parse('2023-01-20');
        yield [$startDate, $eventEndAfterDate, 1, $weekNumber, $eventDate->clone()->subMonth(), false];
        yield [$startDate, $eventEndAfterDate, 1, $weekNumber, $eventDate->clone()->subWeek(), false];
        yield [$startDate, $eventEndAfterDate, 1, $weekNumber, $eventDate->clone()->subDay(), false];
        yield [$startDate, $eventEndAfterDate, 1, $weekNumber, $eventDate->clone()->addMonth(), true];
        yield [$startDate, $eventEndAfterDate, 1, $weekNumber, $eventDate->clone()->addWeek(), false];
        yield [$startDate, $eventEndAfterDate, 1, $weekNumber, $eventDate->clone()->addDays(27), false];
        yield [$startDate, $eventEndAfterDate, 1, $weekNumber, Carbon::parse('2023-06-19'), true];
        yield [$startDate, $eventEndAfterDate, 1, $weekNumber, $lastEventDay, true];
        yield [$startDate, $eventEndAfterDate, 1, $weekNumber, $lastEventDay->clone()->addMonth(), false];
        yield [$startDate, $eventEndAfterDate, 1, null, Carbon::parse('2023-02-06'), true];
        yield [$startDate, $eventEndAfterDate, 1, -1, Carbon::parse('2023-02-27'), true];

        // Repeat every N months
        $eventDate = Carbon::parse('2023-01-20');
        yield [$startDate, $eventEndAfterDate, 2, $weekNumber, $eventDate->clone()->addMonth(), false];
        yield [$startDate, $eventEndAfterDate, 2, $weekNumber, $eventDate->clone()->addMonths(2), true];
        yield [$startDate, $eventEndAfterDate, 2, $weekNumber, $eventDate->clone()->addMonths(3), false];
        yield [$startDate, $eventEndAfterDate, 2, null, Carbon::parse('2023-03-06'), true];
        yield [$startDate, $eventEndAfterDate, 2, -1, Carbon::parse('2023-03-27'), true];

        $eventDate = Carbon::parse('2023-01-17');
        yield [$startDate, $eventEndAfterDate, 3, $weekNumber, $eventDate->clone()->addMonth(), false];
        yield [$startDate, $eventEndAfterDate, 3, $weekNumber, $eventDate->clone()->addMonths(2), false];
        yield [$startDate, $eventEndAfterDate, 3, $weekNumber, $eventDate->clone()->addMonths(3), true];
        yield [$startDate, $eventEndAfterDate, 3, $weekNumber, $eventDate->clone()->addMonths(4), false];
        yield [$startDate, $eventEndAfterDate, 3, $weekNumber, $eventDate->clone()->addMonths(5), false];
        yield [$startDate, $eventEndAfterDate, 3, $weekNumber, $eventDate->clone()->addMonths(6), true];
        yield [$startDate, $eventEndAfterDate, 3, null, Carbon::parse('2023-04-03'), true];
        yield [$startDate, $eventEndAfterDate, 3, -1, Carbon::parse('2023-04-24'), true];

        // End after occurrences
        $eventDate = Carbon::parse('2023-01-20');
        yield [$startDate, $eventEndAfterOccurrences, 1, $weekNumber, $eventDate->clone()->subMonth(), false];
        yield [$startDate, $eventEndAfterOccurrences, 1, $weekNumber, $eventDate->clone()->subWeek(), false];
        yield [$startDate, $eventEndAfterOccurrences, 1, $weekNumber, $eventDate->clone()->subDay(), false];
        yield [$startDate, $eventEndAfterOccurrences, 1, $weekNumber, $eventDate->addMonth(), true];
        yield [$startDate, $eventEndAfterOccurrences, 1, $weekNumber, $eventDate->clone()->addWeek(), false];
        yield [$startDate, $eventEndAfterOccurrences, 1, $weekNumber, $eventDate->clone()->addDays(27), false];
        yield [$startDate, $eventEndAfterOccurrences, 1, $weekNumber, Carbon::parse('2023-06-19'), true];
        yield [$startDate, $eventEndAfterOccurrences, 1, $weekNumber, $lastEventDay, true];
        yield [$startDate, $eventEndAfterOccurrences, 1, $weekNumber, $lastEventDay->clone()->addMonth(), false];
        yield [$startDate, $eventEndAfterOccurrences, 1, null, Carbon::parse('2023-02-06'), true];
        yield [$startDate, $eventEndAfterOccurrences, 1, -1, Carbon::parse('2023-02-27'), true];
    }

    /**
     * @test
     *
     * @dataProvider dataProviderFor5thSaturday
     */
    public function it_determines_if_scheduled_on_date_for_5th_saturday(
        CarbonInterface $startDate,
        EventEnd $eventEnd,
        int $repeatEvery,
        int $weekNumber,
        CarbonInterface $date,
        bool $expectedResult
    ): void {
        $strategy = new MonthlyStrategy($startDate, $eventEnd, $weekNumber, $repeatEvery);

        $this->assertEquals($expectedResult, $strategy->isScheduledOnDate($date));
    }

    public static function dataProviderFor5thSaturday(): iterable
    {
        $startDateWith5thSaturday = Carbon::parse('2024-03-30');
        $endDate = Carbon::parse('2024-12-31');
        $eventEndAfterDate = new EventEnd(EndAfter::DATE, $endDate);
        $weekNumber = 5;

        yield 'march_5th_saturday' => [$startDateWith5thSaturday, $eventEndAfterDate, 1, $weekNumber, Carbon::parse('2024-03-30'), true];
        yield 'february_no_5th_saturday' => [$startDateWith5thSaturday, $eventEndAfterDate, 1, $weekNumber, Carbon::parse('2024-02-24'), false];
        yield 'may_5th_saturday' => [$startDateWith5thSaturday, $eventEndAfterDate, 1, $weekNumber, Carbon::parse('2024-05-25'), true];
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
        int|null $weekNumber,
        CarbonInterface $currentDate,
        CarbonInterface|null $expectedDate
    ): void {
        $strategy = new MonthlyStrategy($startDate, $eventEnd, $weekNumber, $repeatEvery);

        $this->assertEquals(
            $expectedDate?->toDateString(),
            $strategy->getNextOccurrenceDate($currentDate)?->toDateString()
        );
    }

    public static function nextOccurrenceDataProvider(): iterable
    {
        $startDate = Carbon::parse('2023-05-01');
        $endDate = Carbon::parse('2023-12-31');
        $lastEventDay = Carbon::parse('2023-08-21');
        $eventEndAfterDate = new EventEnd(EndAfter::DATE, $endDate);
        $occurrences = 4;
        $eventEndAfterOccurrences = new EventEnd(EndAfter::OCCURRENCES, null, $occurrences);
        $weekNumber = 3;

        // End after date
        $eventDate = Carbon::parse('2023-05-15');
        yield 'd_before_start_date' => [$startDate, $eventEndAfterDate, 1, $weekNumber, $startDate->clone()->subWeek(), $startDate];
        yield 'd_inside_active_period' => [$startDate, $eventEndAfterDate, 1, $weekNumber, $startDate, Carbon::parse('2023-06-19')];
        yield 'd_at_the_end_of_active_period' => [$startDate, $eventEndAfterDate, 1, $weekNumber, $endDate->clone()->subDay(), null];
        yield 'd_after_end_date' => [$startDate, $eventEndAfterDate, 1, $weekNumber, $endDate, null];
        yield 'd_inside_active_period_without_week_number' => [$startDate, $eventEndAfterDate, 1, null, $startDate, Carbon::parse('2023-06-05')];
        yield 'd_inside_active_period_last_of_month' => [$startDate, $eventEndAfterDate, 1, -1, $startDate, Carbon::parse('2023-06-26')];

        // Repeat every N months
        yield 'repeat_after_2_months' => [$startDate, $eventEndAfterDate, 2, $weekNumber, $startDate->clone()->addDay(), Carbon::parse('2023-07-17')];
        yield 'repeat_after_2_months_1' => [$startDate, $eventEndAfterDate, 2, $weekNumber, $startDate->clone(), Carbon::parse('2023-07-17')];
        yield 'repeat_after_2_months_2' => [$startDate, $eventEndAfterDate, 2, $weekNumber, $startDate->clone()->addMonth(), Carbon::parse('2023-07-17')];
        yield 'repeat_after_4_months_1' => [$startDate, $eventEndAfterDate, 2, $weekNumber, $startDate->clone()->addMonths(2), Carbon::parse('2023-07-17')];
        yield 'repeat_after_4_months_2' => [$startDate, $eventEndAfterDate, 2, $weekNumber, $startDate->clone()->addMonths(3), Carbon::parse('2023-09-18')];

        yield 'repeat_after_3_months_1' => [$startDate, $eventEndAfterDate, 3, $weekNumber, $startDate->clone(), Carbon::parse('2023-08-21')];
        yield 'repeat_after_3_months_2' => [$startDate, $eventEndAfterDate, 3, $weekNumber, $startDate->clone()->addMonth(), Carbon::parse('2023-08-21')];
        yield 'repeat_after_3_months_3' => [$startDate, $eventEndAfterDate, 3, $weekNumber, $startDate->clone()->addMonths(2), Carbon::parse('2023-08-21')];
        yield 'repeat_after_3_months_4' => [$startDate, $eventEndAfterDate, 3, $weekNumber, $startDate->clone()->addMonths(3), Carbon::parse('2023-08-21')];
        yield 'repeat_after_3_months_5' => [$startDate, $eventEndAfterDate, 3, $weekNumber, $startDate->clone()->addMonths(4), Carbon::parse('2023-11-20')];
        yield 'repeat_after_3_months_6' => [$startDate, $eventEndAfterDate, 3, $weekNumber, $startDate->clone()->addMonths(5), Carbon::parse('2023-11-20')];

        // End after occurrences
        yield 'o_before_start_date' => [$startDate, $eventEndAfterOccurrences, 1, $weekNumber, $startDate->clone()->subWeek(), $startDate];
        yield 'o_inside_active_period' => [$startDate, $eventEndAfterOccurrences, 1, $weekNumber, $startDate, Carbon::parse('2023-06-19')];
        yield 'o_at_the_end_of_active_period' => [$startDate, $eventEndAfterOccurrences, 1, $weekNumber, $lastEventDay->clone()->subWeek(), $lastEventDay];
        yield 'o_after_end_date' => [$startDate, $eventEndAfterOccurrences, 1, $weekNumber, $lastEventDay->clone(), null];
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
        int|null $weekNumber,
        CarbonInterface $currentDate,
        CarbonInterface|null $expectedDate
    ): void {
        $strategy = new MonthlyStrategy($startDate, $eventEnd, $weekNumber, $repeatEvery);

        $this->assertEquals(
            $expectedDate?->toDateString(),
            $strategy->getPrevOccurrenceDate($currentDate)?->toDateString()
        );
    }

    public static function prevOccurrenceDataProvider(): iterable
    {
        $startDate = Carbon::parse('2023-05-01');
        $endDate = Carbon::parse('2023-12-31');
        $lastEventDay = Carbon::parse('2023-12-18');
        $eventEndAfterDate = new EventEnd(EndAfter::DATE, $endDate);
        $occurrences = 4;
        $eventEndAfterOccurrences = new EventEnd(EndAfter::OCCURRENCES, null, $occurrences);
        $weekNumber = 3;

        // End after date
        yield 'd_before_start_date' => [$startDate, $eventEndAfterDate, 1, $weekNumber, $startDate, null];
        yield 'd_inside_active_period' => [$startDate, $eventEndAfterDate, 1, $weekNumber, $endDate->clone()->subMonth(), Carbon::parse('2023-11-20')];
        yield 'd_at_the_end_of_active_period' => [$startDate, $eventEndAfterDate, 1, $weekNumber, $endDate->clone()->subDay(), $lastEventDay];
        yield 'd_at_the_end_of_active_period_without_week_number' => [$startDate, $eventEndAfterDate, 1, null, $endDate->clone()->subDay(), Carbon::parse('2023-12-04')];
        yield 'd_at_the_end_of_active_period_last_week_of_month' => [$startDate, $eventEndAfterDate, 1, -1, $endDate->clone()->subDay(), Carbon::parse('2023-12-25')];

        // Repeat every N months
        yield 'repeat_after_2_months_1' => [$startDate, $eventEndAfterDate, 2, $weekNumber, $startDate->clone()->addMonths(6), Carbon::parse('2023-09-18')];
        yield 'repeat_after_2_months_2' => [$startDate, $eventEndAfterDate, 2, $weekNumber, $startDate->clone()->addMonths(5), Carbon::parse('2023-09-18')];
        yield 'repeat_after_2_months_3' => [$startDate, $eventEndAfterDate, 2, $weekNumber, $startDate->clone()->addMonths(4), Carbon::parse('2023-07-17')];
        yield 'repeat_after_2_months_4' => [$startDate, $eventEndAfterDate, 2, $weekNumber, $startDate->clone()->addMonths(3), Carbon::parse('2023-07-17')];

        yield 'repeat_after_3_months_1' => [$startDate, $eventEndAfterDate, 3, $weekNumber, $startDate->clone()->addMonths(7), Carbon::parse('2023-11-20')];
        yield 'repeat_after_3_months_2' => [$startDate, $eventEndAfterDate, 3, $weekNumber, $startDate->clone()->addMonths(6), Carbon::parse('2023-08-21')];
        yield 'repeat_after_3_months_3' => [$startDate, $eventEndAfterDate, 3, $weekNumber, $startDate->clone()->addMonths(5), Carbon::parse('2023-08-21')];
        yield 'repeat_after_3_months_4' => [$startDate, $eventEndAfterDate, 3, $weekNumber, $startDate->clone()->addMonths(4), Carbon::parse('2023-08-21')];
        yield 'repeat_after_3_months_5' => [$startDate, $eventEndAfterDate, 3, $weekNumber, $startDate->clone()->addMonths(3), $startDate];
        yield 'repeat_after_3_months_6' => [$startDate, $eventEndAfterDate, 3, $weekNumber, $startDate->clone()->addMonths(2), $startDate];

        // End after occurrences
        yield 'o_before_start_date' => [$startDate, $eventEndAfterOccurrences, 1, $weekNumber, $startDate, null];
        yield 'o_inside_active_period' => [$startDate, $eventEndAfterOccurrences, 1, $weekNumber, $lastEventDay->clone()->subMonth(), Carbon::parse('2023-08-21')];
        yield 'o_at_the_end_of_active_period' => [$startDate, $eventEndAfterOccurrences, 1, $weekNumber, $lastEventDay->clone(), Carbon::parse('2023-08-21')];
        yield 'o_after_end_date' => [$startDate, $eventEndAfterOccurrences, 1, $weekNumber, $lastEventDay->clone()->addWeek(), Carbon::parse('2023-08-21')];
    }

    /**
     * @test
     */
    public function it_returns_expected_values_from_getters(): void
    {
        $startDate = Carbon::parse('2023-01-01');
        $endDate = Carbon::parse('2023-08-31');
        $eventEndAfterDate = new EventEnd(EndAfter::DATE, $endDate);
        $strategy = new MonthlyStrategy($startDate, $eventEndAfterDate);

        $this->assertEquals(ScheduleInterval::MONTHLY, $strategy->getInterval());
    }
}
