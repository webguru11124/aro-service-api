<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Entities\WorkEvent;

use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkEvent;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Tests\TestCase;
use Tests\Tools\Factories\WorkBreakFactory;

class WorkBreakTest extends TestCase
{
    use CloneWorkEvent;

    protected const DURATION_MIN = 15;

    /**
     * @test
     */
    public function it_returns_default_duration(): void
    {
        $workBreak = $this->getWorkBreak();

        $this->assertEquals(static::DURATION_MIN, $workBreak->getDuration()->getTotalMinutes());
    }

    /**
     * @test
     */
    public function duration_can_be_set(): void
    {
        $workBreak = $this->getWorkBreak();

        $durationMin = random_int(16, 20);
        $newDuration = Duration::fromMinutes($durationMin);
        $workBreak->setDuration($newDuration);

        $this->assertEquals($durationMin, $workBreak->getDuration()->getTotalMinutes());
    }

    /**
     * @dataProvider fullFormattedDescriptionDataProvider
     *
     * @test
     */
    public function it_returns_full_formatted_description(TimeWindow|null $timeWindow, $expectedDescription): void
    {
        $workBreak = $this->getWorkBreak();

        if ($timeWindow !== null) {
            $workBreak->setTimeWindow($timeWindow);
        }

        $this->assertEquals($expectedDescription, $workBreak->getFormattedFullDescription());
    }

    public static function fullFormattedDescriptionDataProvider(): iterable
    {
        yield 'without timeWindow set' => [
            null,
            sprintf('%d Min Break.', self::DURATION_MIN),
        ];

        $tz = 'America/Los_Angeles';
        $dateTime = '2022-02-24 09:06:00';
        $expectedTime = '09:06AM';
        $expectedTimeZone = 'PST';

        $startAt = Carbon::parse($dateTime, CarbonTimeZone::create($tz));
        $timeWindow = new TimeWindow($startAt, $startAt->copy()->addMinutes(30));

        yield 'with startAt set LA tz' => [
            $timeWindow,
            sprintf(
                '%d Min Break. Est Start: %s %s',
                self::DURATION_MIN,
                $expectedTime,
                $expectedTimeZone
            ),
        ];

        $tz = 'America/New_York';
        $expectedTimeZone = 'EST';

        $startAt = Carbon::parse($dateTime, CarbonTimeZone::create($tz));
        $timeWindow = new TimeWindow($startAt, $startAt->copy()->addMinutes(30));

        yield 'with startAt set NY tz' => [
            $timeWindow,
            sprintf(
                '%d Min Break. Est Start: %s %s',
                self::DURATION_MIN,
                $expectedTime,
                $expectedTimeZone
            ),
        ];
    }

    protected function getWorkBreak(): WorkBreak
    {
        return new WorkBreak(
            random_int(1, 100),
            'description'
        );
    }

    private function getSubject(): WorkEvent
    {
        return WorkBreakFactory::make();
    }
}
