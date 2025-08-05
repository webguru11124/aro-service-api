<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\PostOptimizationHandlers\RouteValidators;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\PostOptimizationHandlers\RouteValidators\InactivityBeforeFirstAppointment;
use App\Domain\RouteOptimization\ValueObjects\WorkEvent\Waiting;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Tools\Factories\RouteFactory;

class InactivityBeforeFirstAppointmentTest extends TestCase
{
    private const THRESHOLD_WAITING_TIME_IN_MINUTES = 20;

    /**
     * @test
     *
     * @dataProvider dataProvider
     */
    public function it_validates_if_route_has_average_inactivity(Route $route, bool $expectedResult): void
    {
        $validator = new InactivityBeforeFirstAppointment();

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
                Carbon::tomorrow()->hour(13),
                Carbon::tomorrow()->hour(13)->addMinutes(self::THRESHOLD_WAITING_TIME_IN_MINUTES)
            ))),
            true,
        ];
        yield [
            RouteFactory::make()->addWorkEvent(new Waiting(new TimeWindow(
                Carbon::tomorrow()->hour(7),
                Carbon::tomorrow()->hour(7)->addMinutes(self::THRESHOLD_WAITING_TIME_IN_MINUTES)
            ))),
            false,
        ];
        yield [
            RouteFactory::make()->addWorkEvent(new Waiting(new TimeWindow(
                Carbon::tomorrow()->hour(7),
                Carbon::tomorrow()->hour(7)->addMinutes(self::THRESHOLD_WAITING_TIME_IN_MINUTES - 1)
            ))),
            true,
        ];
        yield [
            RouteFactory::make()->addWorkEvent(new Waiting(new TimeWindow(
                Carbon::tomorrow()->hour(7),
                Carbon::tomorrow()->hour(7)->addMinutes(self::THRESHOLD_WAITING_TIME_IN_MINUTES + 1)
            ))),
            false,
        ];
    }
}
