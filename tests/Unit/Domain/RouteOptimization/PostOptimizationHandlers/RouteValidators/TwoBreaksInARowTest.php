<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\PostOptimizationHandlers\RouteValidators;

use Carbon\Carbon;
use Tests\TestCase;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\TravelFactory;
use Tests\Tools\Factories\WorkBreakFactory;
use Tests\Tools\Factories\AppointmentFactory;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Domain\RouteOptimization\ValueObjects\WorkEvent\Waiting;
use App\Domain\RouteOptimization\PostOptimizationHandlers\RouteValidators\TwoBreaksInARow;

class TwoBreaksInARowTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider dataProvider
     */
    public function it_validates_if_route_has_two_breaks_in_a_row(
        Route $route,
        bool $expectedResult,
    ): void {
        $validator = new TwoBreaksInARow();

        $this->assertEquals($expectedResult, $validator->validate($route));
    }

    public static function dataProvider(): iterable
    {
        yield 'route without two breaks in row' => [
            RouteFactory::make(),
            true,
        ];

        yield 'route with two breaks in row' => [
            RouteFactory::make([
                'workEvents' => [
                    WorkBreakFactory::make(),
                    WorkBreakFactory::make(),
                ],
            ]),
            false,
        ];

        yield 'route with travel between work breaks' => [
            RouteFactory::make([
                'workEvents' => [
                    WorkBreakFactory::make([
                        'timeWindow' => new TimeWindow(
                            Carbon::now()->hour(6),
                            Carbon::now()->hour(6)->addMinutes(30),
                        ),
                    ]),
                    TravelFactory::make([
                        'timeWindow' => new TimeWindow(
                            Carbon::now()->hour(7),
                            Carbon::now()->hour(7)->addMinutes(30),
                        ),
                    ]),
                    WorkBreakFactory::make([
                        'timeWindow' => new TimeWindow(
                            Carbon::now()->hour(8),
                            Carbon::now()->hour(8)->addMinutes(30),
                        ),
                    ]),
                ],
            ]),
            false,
        ];

        yield 'route with waiting between work breaks' => [
            RouteFactory::make([
                'workEvents' => [
                    WorkBreakFactory::make([
                        'timeWindow' => new TimeWindow(
                            Carbon::now()->hour(6),
                            Carbon::now()->hour(6)->addMinutes(30),
                        ),
                    ]),
                    new Waiting(new TimeWindow(
                        Carbon::now()->hour(7),
                        Carbon::now()->hour(7)->addMinutes(30),
                    )),
                    WorkBreakFactory::make([
                        'timeWindow' => new TimeWindow(
                            Carbon::now()->hour(8),
                            Carbon::now()->hour(8)->addMinutes(30),
                        ),
                    ]),
                ],
            ]),
            false,
        ];

        yield 'route with appointment between work breaks' => [
            RouteFactory::make([
                'workEvents' => [
                    WorkBreakFactory::make([
                        'timeWindow' => new TimeWindow(
                            Carbon::now()->hour(6),
                            Carbon::now()->hour(6)->addMinutes(30),
                        ),
                    ]),
                    AppointmentFactory::make([
                        'timeWindow' => new TimeWindow(
                            Carbon::now()->hour(7),
                            Carbon::now()->hour(7)->addMinutes(30),
                        ),
                    ]),
                    WorkBreakFactory::make([
                        'timeWindow' => new TimeWindow(
                            Carbon::now()->hour(8),
                            Carbon::now()->hour(8)->addMinutes(30),
                        ),
                    ]),
                ],
            ]),
            true,
        ];
    }
}
