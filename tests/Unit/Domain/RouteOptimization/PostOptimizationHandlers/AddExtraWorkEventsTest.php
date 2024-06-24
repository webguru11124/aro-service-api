<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\PostOptimizationHandlers;

use App\Domain\Contracts\FeatureFlagService;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Travel;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkEvent;
use App\Domain\RouteOptimization\PostOptimizationHandlers\AddExtraWorkEvents;
use App\Domain\RouteOptimization\ValueObjects\WorkEvent\ExtraWork;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\LunchFactory;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\ServiceProFactory;
use Tests\Tools\Factories\TravelFactory;
use Tests\Tools\Factories\WorkBreakFactory;

class AddExtraWorkEventsTest extends TestCase
{
    private const MIN_EXTRA_WORK_EVENT_DURATION = 30;

    private AddExtraWorkEvents $handler;
    private MockInterface|FeatureFlagService $mockFeatureFlagService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockFeatureFlagService = Mockery::mock(FeatureFlagService::class);
        $this->handler = new AddExtraWorkEvents($this->mockFeatureFlagService);
    }

    /**
     * @test
     */
    public function it_adds_extra_work_events_for_travel_and_work_break_over_30_minutes(): void
    {
        $optimizationState = $this->getOptimizationStateForExtraWorkTest();

        /** @var Route $route */
        $route = $optimizationState->getRoutes()->first();

        $this->mockFeatureFlagService->shouldReceive('isFeatureEnabledForOffice')
            ->andReturnTrue();

        $this->handler->process($optimizationState);

        $extraWorkEvents = $route->getWorkEvents()->filter(fn (WorkEvent $workEvent) => $workEvent instanceof ExtraWork);

        $expectedExtraWorkCount = $this->calculateExpectedExtraWorkCountForTravel($route);

        $this->assertEquals($expectedExtraWorkCount, $extraWorkEvents->count());
    }

    private function getOptimizationStateForExtraWorkTest(): OptimizationState
    {
        $time = Carbon::tomorrow()->hour(8);

        $travelMinutes = fn () => $this->faker->numberBetween(30, 35);
        $appointmentMinutes = fn () => $this->faker->numberBetween(20, 25);
        $lunchMinutes = 30;
        $breakMinutes = 15;

        $timeWindow = fn (int $eventMinutes) => new TimeWindow(
            $time->addMinute()->clone(),
            $time->addMinutes($eventMinutes)->clone()
        );

        $servicePro = ServiceProFactory::make([
            'workingHours' => new TimeWindow(
                $time->clone(),
                $time->clone()->addHours(8)
            ),
        ]);
        $route = RouteFactory::make([
            'servicePro' => $servicePro,
            'workEvents' => [
                AppointmentFactory::make(['timeWindow' => $timeWindow($appointmentMinutes())]),
                TravelFactory::make(['timeWindow' => $timeWindow($travelMinutes())]),
                AppointmentFactory::make(['timeWindow' => $timeWindow($appointmentMinutes())]),
                LunchFactory::make(['timeWindow' => $timeWindow($lunchMinutes)]),
                AppointmentFactory::make(['timeWindow' => $timeWindow($appointmentMinutes())]),
                TravelFactory::make(['timeWindow' => $timeWindow($travelMinutes())]),
                AppointmentFactory::make(['timeWindow' => $timeWindow($appointmentMinutes())]),
                WorkBreakFactory::make(['timeWindow' => $timeWindow($breakMinutes)]),
                AppointmentFactory::make(['timeWindow' => $timeWindow($appointmentMinutes())]),
            ],
        ]);

        return OptimizationStateFactory::make(['routes' => new Collection([$route])]);
    }

    private function calculateExpectedExtraWorkCountForTravel(Route $route): int
    {
        return $route->getTravelEvents()
            ->filter(fn (Travel $travelEvent) => $travelEvent->getTimeWindow()->getTotalMinutes() >= self::MIN_EXTRA_WORK_EVENT_DURATION)
            ->count();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->handler, $this->mockFeatureFlagService);
    }
}
