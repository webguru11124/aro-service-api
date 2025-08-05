<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Calendar\IntervalStrategies;

use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\IntervalStrategies\OnceStrategy;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Tests\TestCase;

class OnceStrategyTest extends TestCase
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
        CarbonInterface $date,
        bool $expectedResult
    ): void {
        $strategy = new OnceStrategy($startDate);

        $this->assertEquals($expectedResult, $strategy->isScheduledOnDate($date));
    }

    public static function dataProvider(): iterable
    {
        $startDate = Carbon::parse('2023-01-01');

        yield [$startDate, Carbon::parse('2022-01-01'), false];
        yield [$startDate, $startDate->clone()->subMonth(), false];
        yield [$startDate, $startDate->clone()->subWeek(), false];
        yield [$startDate, $startDate->clone()->subDay(), false];
        yield [$startDate, $startDate, true];
        yield [$startDate, $startDate->clone()->addDays(random_int(1, 30)), false];
        yield [$startDate, $startDate->clone()->addWeek(), false];
        yield [$startDate, $startDate->clone()->addMonth(), false];
        yield [$startDate, Carbon::parse('2024-01-01'), false];
    }

    /**
     * @test
     *
     * ::getNextOccurrenceDate
     */
    public function it_returns_null_for_next_occurrence(): void
    {
        $startDate = Carbon::parse('2023-06-01');
        $strategy = new OnceStrategy($startDate);

        $this->assertNull($strategy->getNextOccurrenceDate($startDate->clone()->addDay()));
    }

    /**
     * @test
     *
     * ::getNextOccurrenceDate
     */
    public function it_returns_start_date_for_next_occurrence(): void
    {
        $startDate = Carbon::parse('2023-06-01');
        $strategy = new OnceStrategy($startDate);

        $this->assertEquals(
            '2023-06-01',
            $strategy->getNextOccurrenceDate($startDate->clone()->subWeek())->toDateString()
        );
    }

    /**
     * @test
     *
     * ::getPrevOccurrenceDate
     */
    public function it_returns_null_for_prev_occurrence(): void
    {
        $startDate = Carbon::parse('2023-06-01');
        $strategy = new OnceStrategy($startDate);

        $this->assertNull($strategy->getPrevOccurrenceDate($startDate->clone()->subDay()));
    }

    /**
     * @test
     *
     * ::getPrevOccurrenceDate
     */
    public function it_returns_start_date_for_prev_occurrence(): void
    {
        $startDate = Carbon::parse('2023-06-01');
        $strategy = new OnceStrategy($startDate);

        $this->assertEquals(
            '2023-06-01',
            $strategy->getPrevOccurrenceDate($startDate->clone()->addWeek())->toDateString()
        );
    }

    /**
     * @test
     */
    public function it_returns_expected_values_from_getters(): void
    {
        $startDate = Carbon::parse('2023-01-01');
        $strategy = new OnceStrategy($startDate);

        $this->assertEquals(ScheduleInterval::ONCE, $strategy->getInterval());
        $this->assertNull($strategy->getOccurrence());
    }
}
