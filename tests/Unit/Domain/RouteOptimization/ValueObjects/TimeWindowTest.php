<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\ValueObjects;

use App\Domain\SharedKernel\Exceptions\InvalidTimeWindowException;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Tests\TestCase;

class TimeWindowTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_start_at_correctly(): void
    {
        $start = Carbon::create(2023, 5, 15, 12);
        $end = Carbon::create(2023, 5, 15, 14);
        $timeWindow = new TimeWindow($start, $end);

        $this->assertEquals($start, $timeWindow->getStartAt());
    }

    /**
     * @test
     */
    public function it_returns_end_at_correctly(): void
    {
        $start = Carbon::create(2023, 5, 15, 12);
        $end = Carbon::create(2023, 5, 15, 14);
        $timeWindow = new TimeWindow($start, $end);

        $this->assertEquals($end, $timeWindow->getEndAt());
    }

    /**
     * @test
     */
    public function it_calculates_total_minutes_correctly(): void
    {
        $start = Carbon::create(2023, 5, 15, 12);
        $end = Carbon::create(2023, 5, 15, 14, 15);
        $timeWindow = new TimeWindow($start, $end);

        $this->assertEquals(135, $timeWindow->getTotalMinutes());
    }

    /**
     * @test
     */
    public function it_calculates_total_seconds_correctly(): void
    {
        $start = Carbon::create(2023, 5, 15, 12);
        $end = Carbon::create(2023, 5, 15, 12, 2, 30);
        $timeWindow = new TimeWindow($start, $end);

        $this->assertEquals(150, $timeWindow->getTotalSeconds());
    }

    /**
     * @test
     */
    public function it_calculates_duration_correctly(): void
    {
        $start = Carbon::create(2023, 5, 15, 12);
        $end = Carbon::create(2023, 5, 15, 12, 2, 30);
        $timeWindow = new TimeWindow($start, $end);

        $this->assertEquals(150, $timeWindow->getDuration()->getTotalSeconds());
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_start_is_more_than_end(): void
    {
        $this->expectException(InvalidTimeWindowException::class);

        new TimeWindow(
            Carbon::tomorrow()->hour(9),
            Carbon::tomorrow()->hour(9)->subMinute()
        );
    }

    /**
     * @test
     *
     * @dataProvider intersectionDataProvider
     */
    public function it_determines_intersection_with_given_time_window(
        TimeWindow $left,
        TimeWindow $right,
        TimeWindow|null $expected
    ): void {
        $intersectionLeftRight = $left->getIntersection($right);
        $intersectionRightLeft = $right->getIntersection($left);

        $this->assertEquals($intersectionLeftRight?->getStartAt()->timestamp, $expected?->getStartAt()->timestamp);
        $this->assertEquals($intersectionLeftRight?->getEndAt()->timestamp, $expected?->getEndAt()->timestamp);
        $this->assertEquals($intersectionRightLeft?->getStartAt()->timestamp, $expected?->getStartAt()->timestamp);
        $this->assertEquals($intersectionRightLeft?->getEndAt()->timestamp, $expected?->getEndAt()->timestamp);
    }

    public static function intersectionDataProvider(): iterable
    {
        $day = Carbon::tomorrow();

        yield [
            new TimeWindow($day->clone()->hour(8), $day->clone()->hour(12)),
            new TimeWindow($day->clone()->hour(9), $day->clone()->hour(11)),
            new TimeWindow($day->clone()->hour(9), $day->clone()->hour(11)),
        ];

        yield [
            new TimeWindow($day->clone()->hour(8), $day->clone()->hour(12)),
            new TimeWindow($day->clone()->hour(10), $day->clone()->hour(14)),
            new TimeWindow($day->clone()->hour(10), $day->clone()->hour(12)),
        ];

        yield [
            new TimeWindow($day->clone()->hour(8), $day->clone()->hour(12)),
            new TimeWindow($day->clone()->hour(12), $day->clone()->hour(14)),
            null,
        ];

        yield [
            new TimeWindow($day->clone()->hour(8), $day->clone()->hour(12)),
            new TimeWindow($day->clone()->hour(13), $day->clone()->hour(14)),
            null,
        ];
    }
}
