<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Entities\WorkEvent;

use App\Domain\RouteOptimization\Entities\WorkEvent\Lunch;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;

class LunchTest extends WorkBreakTest
{
    protected const DURATION_MIN = 30;

    public static function fullFormattedDescriptionDataProvider(): iterable
    {
        yield 'without startAt set' => [
            null,
            'Lunch Break.',
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
                'Lunch Break. Est Start: %s %s',
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
                'Lunch Break. Est Start: %s %s',
                $expectedTime,
                $expectedTimeZone
            ),
        ];
    }

    protected function getWorkBreak(): Lunch
    {
        return new Lunch(
            random_int(1, 100),
            'description'
        );
    }
}
