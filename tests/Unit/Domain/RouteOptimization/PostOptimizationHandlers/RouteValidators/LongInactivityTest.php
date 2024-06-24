<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\PostOptimizationHandlers\RouteValidators;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\PostOptimizationHandlers\RouteValidators\LongInactivity;
use App\Domain\RouteOptimization\ValueObjects\WorkEvent\Waiting;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Tools\Factories\RouteFactory;

class LongInactivityTest extends TestCase
{
    private const THRESHOLD_WAITING_TIME_IN_MINUTES = 60;

    /**
     * @test
     *
     * @dataProvider dataProvider
     */
    public function it_validates_if_route_has_average_inactivity(Route $route, bool $expectedResult): void
    {
        $validator = new LongInactivity();

        $this->assertEquals($expectedResult, $validator->validate($route));
    }

    public static function dataProvider(): iterable
    {
        yield [
            RouteFactory::make(),
            true,
        ];
        yield [
            RouteFactory::make()->addWorkEvent(new Waiting(new TimeWindow(
                Carbon::tomorrow()->hour(10),
                Carbon::tomorrow()->hour(10)->addMinutes(self::THRESHOLD_WAITING_TIME_IN_MINUTES)
            ))),
            false,
        ];
        yield [
            RouteFactory::make()->addWorkEvent(new Waiting(new TimeWindow(
                Carbon::tomorrow()->hour(10),
                Carbon::tomorrow()->hour(10)->addMinutes(self::THRESHOLD_WAITING_TIME_IN_MINUTES - 1)
            ))),
            true,
        ];
        yield [
            RouteFactory::make()->addWorkEvent(new Waiting(new TimeWindow(
                Carbon::tomorrow()->hour(10),
                Carbon::tomorrow()->hour(10)->addMinutes(self::THRESHOLD_WAITING_TIME_IN_MINUTES + 1)
            ))),
            false,
        ];
    }
}
