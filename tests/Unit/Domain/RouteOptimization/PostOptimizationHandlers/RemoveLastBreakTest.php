<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\PostOptimizationHandlers;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\PostOptimizationHandlers\RemoveLastBreak;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\LunchFactory;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\WorkBreakFactory;

class RemoveLastBreakTest extends TestCase
{
    /**
     * @test
     */
    public function it_removes_last_break_if_there_is_no_appointments_after(): void
    {
        $handler = new RemoveLastBreak();

        $day = Carbon::tomorrow();

        /** @var Route $route */
        $route = RouteFactory::make([
            'workEvents' => [
                AppointmentFactory::make([
                    'timeWindow' => new TimeWindow(
                        $day->clone()->hour(8),
                        $day->clone()->hour(9)
                    ),
                ]),
                AppointmentFactory::make([
                    'timeWindow' => new TimeWindow(
                        $day->clone()->hour(9),
                        $day->clone()->hour(10)
                    ),
                ]),
                $firstBreak = WorkBreakFactory::make([
                    'timeWindow' => new TimeWindow(
                        $day->clone()->hour(10),
                        $day->clone()->hour(10)->minutes(15)
                    ),
                ]),
                AppointmentFactory::make([
                    'timeWindow' => new TimeWindow(
                        $day->clone()->hour(10)->minutes(15),
                        $day->clone()->hour(11)
                    ),
                ]),
                AppointmentFactory::make([
                    'timeWindow' => new TimeWindow(
                        $day->clone()->hour(11),
                        $day->clone()->hour(12)
                    ),
                ]),
                LunchFactory::make([
                    'timeWindow' => new TimeWindow(
                        $day->clone()->hour(12),
                        $day->clone()->hour(12)->minutes(30)
                    ),
                ]),
                WorkBreakFactory::make([
                    'timeWindow' => new TimeWindow(
                        $day->clone()->hour(12)->minutes(30),
                        $day->clone()->hour(12)->minutes(45)
                    ),
                ]),
            ],
        ]);

        $optimizationState = OptimizationStateFactory::make([
            'routes' => new Collection([$route]),
        ]);

        $handler->process($optimizationState);

        $allBreaks = $route->getWorkBreaks();

        $this->assertEquals(1, $allBreaks->count());
        $this->assertSame($firstBreak->getId(), $allBreaks->first()->getId());
    }
}
