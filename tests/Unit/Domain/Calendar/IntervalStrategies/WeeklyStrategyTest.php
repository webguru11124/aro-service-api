<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Calendar\IntervalStrategies;

use App\Domain\Calendar\Enums\EndAfter;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\Enums\WeekDay;
use App\Domain\Calendar\IntervalStrategies\WeeklyStrategy;
use App\Domain\Calendar\ValueObjects\EventEnd;
use App\Domain\Calendar\ValueObjects\WeeklyOccurrence;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Tests\TestCase;

class WeeklyStrategyTest extends TestCase
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
        WeeklyOccurrence $occurrence,
        CarbonInterface $date,
        bool $expectedResult
    ): void {
        $strategy = new WeeklyStrategy($startDate, $eventEnd, $repeatEvery, $occurrence);

        $this->assertEquals($expectedResult, $strategy->isScheduledOnDate($date));
    }

    public static function dataProvider(): iterable
    {
        $startDate = Carbon::parse('2023-01-01');
        $endDate = Carbon::parse('2023-12-31');
        $eventEndAfterDate = new EventEnd(EndAfter::DATE, $endDate);
        $firstEventDay = $startDate->clone()->addDay();
        $secondEventDay = $firstEventDay->clone()->addDays(2);
        $weekDay = WeekDay::from(strtolower($firstEventDay->dayName));
        $lastEventDay = $endDate->clone();

        while (strtolower($lastEventDay->dayName) !== strtolower($weekDay->value)) {
            $lastEventDay->subDay();
        }

        $occurrence = new WeeklyOccurrence(collect([
            $weekDay,
            WeekDay::from(strtolower($secondEventDay->dayName)),
        ]));

        $occurrences = 5;
        $eventEndAfterOccurrences = new EventEnd(EndAfter::OCCURRENCES, null, $occurrences);

        // End after date
        yield [$startDate, $eventEndAfterDate, 1, $occurrence, $firstEventDay->clone()->subWeeks(2), false];
        yield [$startDate, $eventEndAfterDate, 1, $occurrence, $firstEventDay->clone()->subWeek(), false];
        yield [$startDate, $eventEndAfterDate, 1, $occurrence, $firstEventDay->clone()->subDay(), false];
        yield [$startDate, $eventEndAfterDate, 1, $occurrence, $firstEventDay, true];
        yield [$startDate, $eventEndAfterDate, 1, $occurrence, $firstEventDay->clone()->addWeek(), true];
        yield [$startDate, $eventEndAfterDate, 1, $occurrence, $secondEventDay, true];
        yield [$startDate, $eventEndAfterDate, 1, $occurrence, $secondEventDay->clone()->addWeek(), true];
        yield [$startDate, $eventEndAfterDate, 1, $occurrence, $firstEventDay->clone()->addWeeks(10), true];
        yield [$startDate, $eventEndAfterDate, 1, $occurrence, $lastEventDay, true];
        yield [$startDate, $eventEndAfterDate, 1, $occurrence, $lastEventDay->clone()->addWeek(), false];
        yield [$startDate, $eventEndAfterDate, 1, $occurrence, $lastEventDay->clone()->addWeeks(10), false];

        // Repeat every N days
        yield [$startDate, $eventEndAfterDate, 2, $occurrence, $firstEventDay->clone()->addWeek(), false];
        yield [$startDate, $eventEndAfterDate, 2, $occurrence, $firstEventDay->clone()->addWeeks(2), true];
        yield [$startDate, $eventEndAfterDate, 2, $occurrence, $firstEventDay->clone()->addWeeks(3), false];

        yield [$startDate, $eventEndAfterDate, 3, $occurrence, $firstEventDay->clone()->addWeek(), false];
        yield [$startDate, $eventEndAfterDate, 3, $occurrence, $firstEventDay->clone()->addWeeks(2), false];
        yield [$startDate, $eventEndAfterDate, 3, $occurrence, $firstEventDay->clone()->addWeeks(3), true];
        yield [$startDate, $eventEndAfterDate, 3, $occurrence, $firstEventDay->clone()->addWeeks(4), false];
        yield [$startDate, $eventEndAfterDate, 3, $occurrence, $firstEventDay->clone()->addWeeks(5), false];
        yield [$startDate, $eventEndAfterDate, 3, $occurrence, $firstEventDay->clone()->addWeeks(6), true];

        // End after occurrences
        yield [$startDate, $eventEndAfterOccurrences, 1, $occurrence, $firstEventDay->clone()->subWeeks(2), false];
        yield [$startDate, $eventEndAfterOccurrences, 1, $occurrence, $firstEventDay->clone()->subWeek(), false];
        yield [$startDate, $eventEndAfterOccurrences, 1, $occurrence, $firstEventDay->clone()->subDay(), false];
        yield [$startDate, $eventEndAfterOccurrences, 1, $occurrence, $firstEventDay, true];
        yield [$startDate, $eventEndAfterOccurrences, 1, $occurrence, $firstEventDay->clone()->addWeek(), true];

        yield [$startDate, $eventEndAfterOccurrences, 1, $occurrence, $secondEventDay, true];
        yield [$startDate, $eventEndAfterOccurrences, 1, $occurrence, $secondEventDay->clone()->addWeek(), true];
        yield [$startDate, $eventEndAfterOccurrences, 1, $occurrence, $firstEventDay->clone()->addWeeks(2), true];
        yield [$startDate, $eventEndAfterOccurrences, 1, $occurrence, $secondEventDay->clone()->addWeeks(2), false];

        // Repeat every N days and end after occurrences
        yield [$startDate, $eventEndAfterOccurrences, 2, $occurrence, $firstEventDay->clone()->addWeek(), false];
        yield [$startDate, $eventEndAfterOccurrences, 2, $occurrence, $firstEventDay->clone()->addWeeks(2), true];
        yield [$startDate, $eventEndAfterOccurrences, 2, $occurrence, $firstEventDay->clone()->addWeeks(3), false];
        yield [$startDate, $eventEndAfterOccurrences, 2, $occurrence, $firstEventDay->clone()->addWeeks(4), true];

        yield [$startDate, $eventEndAfterOccurrences, 3, $occurrence, $secondEventDay->clone()->addWeek(), false];
        yield [$startDate, $eventEndAfterOccurrences, 3, $occurrence, $secondEventDay->clone()->addWeeks(2), false];
        yield [$startDate, $eventEndAfterOccurrences, 3, $occurrence, $secondEventDay->clone()->addWeeks(3), true];
        yield [$startDate, $eventEndAfterOccurrences, 3, $occurrence, $secondEventDay->clone()->addWeeks(4), false];
        yield [$startDate, $eventEndAfterOccurrences, 3, $occurrence, $secondEventDay->clone()->addWeeks(5), false];
        yield [$startDate, $eventEndAfterOccurrences, 3, $occurrence, $secondEventDay->clone()->addWeeks(6), true];
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
        $occurrence = new WeeklyOccurrence(collect([WeekDay::WEDNESDAY]));
        $strategy = new WeeklyStrategy($startDate, $eventEnd, $repeatEvery, $occurrence);

        $this->assertEquals(
            $expectedDate?->toDateString(),
            $strategy->getNextOccurrenceDate($currentDate)?->toDateString()
        );
    }

    public static function nextOccurrenceDataProvider(): iterable
    {
        $startDate = Carbon::parse('2023-05-01');
        $endDate = Carbon::parse('2023-08-31');
        $firstEventDate = Carbon::parse('2023-05-03');
        $eventEndAfterDate = new EventEnd(EndAfter::DATE, $endDate);
        $occurrences = 5;
        $eventEndAfterOccurrences = new EventEnd(EndAfter::OCCURRENCES, null, $occurrences);

        // End after date
        yield 'd_before_start_date' => [$startDate, $eventEndAfterDate, 1, $startDate->clone()->subWeek(), $startDate];
        yield 'd_inside_active_period' => [$startDate, $eventEndAfterDate, 1, $startDate, $firstEventDate];
        yield 'd_at_the_end_of_active_period' => [$startDate, $eventEndAfterDate, 1, $endDate->clone()->subDay(), null];
        yield 'd_after_end_date' => [$startDate, $eventEndAfterDate, 1, $endDate, null];

        // Repeat every N weeks
        yield 'repeat_after_2_weeks_1' => [$startDate, $eventEndAfterDate, 2, $firstEventDate->clone(), $firstEventDate->clone()->addWeeks(2)];
        yield 'repeat_after_2_weeks_2' => [$startDate, $eventEndAfterDate, 2, $firstEventDate->clone()->addWeek(), $firstEventDate->clone()->addWeeks(2)];
        yield 'repeat_after_4_weeks_1' => [$startDate, $eventEndAfterDate, 2, $firstEventDate->clone()->addWeeks(2), $firstEventDate->clone()->addWeeks(4)];
        yield 'repeat_after_4_weeks_2' => [$startDate, $eventEndAfterDate, 2, $firstEventDate->clone()->addWeeks(3), $firstEventDate->clone()->addWeeks(4)];

        yield 'repeat_after_3_weeks_1' => [$startDate, $eventEndAfterDate, 3, $firstEventDate->clone(), $firstEventDate->clone()->addWeeks(3)];
        yield 'repeat_after_3_weeks_2' => [$startDate, $eventEndAfterDate, 3, $firstEventDate->clone()->addWeek(), $firstEventDate->clone()->addWeeks(3)];
        yield 'repeat_after_3_weeks_3' => [$startDate, $eventEndAfterDate, 3, $firstEventDate->clone()->addWeeks(2), $firstEventDate->clone()->addWeeks(3)];
        yield 'repeat_after_3_weeks_4' => [$startDate, $eventEndAfterDate, 3, $firstEventDate->clone()->addWeeks(3), $firstEventDate->clone()->addWeeks(6)];
        yield 'repeat_after_3_weeks_5' => [$startDate, $eventEndAfterDate, 3, $firstEventDate->clone()->addWeeks(4), $firstEventDate->clone()->addWeeks(6)];
        yield 'repeat_after_3_weeks_6' => [$startDate, $eventEndAfterDate, 3, $firstEventDate->clone()->addWeeks(5), $firstEventDate->clone()->addWeeks(6)];

        // End after occurrences
        yield 'o_before_start_date' => [$startDate, $eventEndAfterOccurrences, 1, $startDate->clone()->subWeek(), $startDate];
        yield 'o_inside_active_period' => [$startDate, $eventEndAfterOccurrences, 1, $startDate, $firstEventDate];
        yield 'o_at_the_end_of_active_period' => [
            $startDate,
            $eventEndAfterOccurrences,
            1,
            $firstEventDate->clone()->addWeeks($occurrences - 1)->subDay(),
            $firstEventDate->clone()->addWeeks($occurrences - 1),
        ];
        yield 'o_after_end_date' => [
            $startDate,
            $eventEndAfterOccurrences,
            1,
            $firstEventDate->clone()->addWeeks($occurrences - 1),
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
        $occurrence = new WeeklyOccurrence(collect([WeekDay::WEDNESDAY]));
        $strategy = new WeeklyStrategy($startDate, $eventEnd, $repeatEvery, $occurrence);

        $this->assertEquals(
            $expectedDate?->toDateString(),
            $strategy->getPrevOccurrenceDate($currentDate)?->toDateString()
        );
    }

    public static function prevOccurrenceDataProvider(): iterable
    {
        $startDate = Carbon::parse('2023-05-01');
        $endDate = Carbon::parse('2023-08-31');
        $firstEventDate = Carbon::parse('2023-05-03');
        $eventEndAfterDate = new EventEnd(EndAfter::DATE, $endDate);
        $occurrences = 3;
        $eventEndAfterOccurrences = new EventEnd(EndAfter::OCCURRENCES, null, $occurrences);

        // End after date
        yield 'd_before_start_date' => [$startDate, $eventEndAfterDate, 1, $startDate, null];
        yield 'd_inside_active_period' => [$startDate, $eventEndAfterDate, 1, $endDate->clone()->subMonth(), Carbon::parse('2023-07-26')];
        yield 'd_at_the_end_of_active_period' => [$startDate, $eventEndAfterDate, 1, $endDate->clone()->subDay(), Carbon::parse('2023-08-23')];
        yield 'd_after_end_date' => [$startDate, $eventEndAfterDate, 1, $endDate->clone()->addWeek(), Carbon::parse('2023-08-30')];
        yield 'd_edge_case_whe_one_day_event_has_incorrect_occurrence_weekday' => [$startDate, new EventEnd(EndAfter::DATE, $startDate), 1, $endDate->clone()->addWeek(), null];

        // Repeat every N days
        yield 'repeat_after_2_weeks_1' => [$startDate, $eventEndAfterDate, 2, $firstEventDate->clone()->addWeeks(10), $firstEventDate->clone()->addWeeks(8)];
        yield 'repeat_after_2_weeks_2' => [$startDate, $eventEndAfterDate, 2, $firstEventDate->clone()->addWeeks(9), $firstEventDate->clone()->addWeeks(8)];
        yield 'repeat_after_4_weeks_1' => [$startDate, $eventEndAfterDate, 2, $firstEventDate->clone()->addWeeks(8), $firstEventDate->clone()->addWeeks(6)];
        yield 'repeat_after_4_weeks_2' => [$startDate, $eventEndAfterDate, 2, $firstEventDate->clone()->addWeeks(7), $firstEventDate->clone()->addWeeks(6)];

        yield 'repeat_after_3_weeks_1' => [$startDate, $eventEndAfterDate, 3, $firstEventDate->clone()->addWeeks(10), $firstEventDate->clone()->addWeeks(9)];
        yield 'repeat_after_3_weeks_2' => [$startDate, $eventEndAfterDate, 3, $firstEventDate->clone()->addWeeks(9), $firstEventDate->clone()->addWeeks(6)];
        yield 'repeat_after_3_weeks_3' => [$startDate, $eventEndAfterDate, 3, $firstEventDate->clone()->addWeeks(8), $firstEventDate->clone()->addWeeks(6)];
        yield 'repeat_after_3_weeks_4' => [$startDate, $eventEndAfterDate, 3, $firstEventDate->clone()->addWeeks(7), $firstEventDate->clone()->addWeeks(6)];
        yield 'repeat_after_3_weeks_5' => [$startDate, $eventEndAfterDate, 3, $firstEventDate->clone()->addWeeks(6), $firstEventDate->clone()->addWeeks(3)];
        yield 'repeat_after_3_weeks_6' => [$startDate, $eventEndAfterDate, 3, $firstEventDate->clone()->addWeeks(5), $firstEventDate->clone()->addWeeks(3)];

        // End after occurrences
        yield 'o_before_start_date' => [$startDate, $eventEndAfterOccurrences, 1, $startDate, null];
        yield 'o_inside_active_period' => [$startDate, $eventEndAfterOccurrences, 1, $firstEventDate->clone()->addWeeks(2), $firstEventDate->clone()->addWeek()];
        yield 'o_at_the_end_of_active_period' => [$startDate, $eventEndAfterOccurrences, 1, $firstEventDate->clone()->addWeeks($occurrences - 1), $firstEventDate->clone()->addWeeks($occurrences - 2)];
        yield 'o_after_end_date' => [$startDate, $eventEndAfterOccurrences, 1, $firstEventDate->clone()->addWeeks($occurrences), $firstEventDate->clone()->addWeeks($occurrences - 1)];
    }

    /**
     * @test
     */
    public function it_returns_expected_values_from_getters(): void
    {
        $startDate = Carbon::parse('2023-01-01');
        $endDate = Carbon::parse('2023-12-31');
        $firstEventDay = $startDate->clone()->addDays(random_int(0, 6));
        $weekDay = WeekDay::from(strtolower($firstEventDay->dayName));

        $occurrence = new WeeklyOccurrence(collect([$weekDay]));
        $eventEndAfterDate = new EventEnd(EndAfter::DATE, $endDate);

        $strategy = new WeeklyStrategy($startDate, $eventEndAfterDate, 1, $occurrence);

        $this->assertEquals(ScheduleInterval::WEEKLY, $strategy->getInterval());
        $this->assertEquals($strategy->getOccurrence(), $occurrence);
    }
}
