<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Scheduling\Services;

use App\Domain\Scheduling\Entities\ScheduledRoute;
use App\Domain\Scheduling\Entities\SchedulingState;
use App\Domain\Scheduling\Helpers\TriangulateHelper;
use App\Domain\Scheduling\Services\AppointmentSchedulingService;
use App\Domain\Scheduling\ValueObjects\Triangle;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\Scheduling\AppointmentFactory;
use Tests\Tools\Factories\Scheduling\PendingServiceFactory;
use Tests\Tools\Factories\Scheduling\ScheduledRouteFactory;
use Tests\Tools\Factories\Scheduling\SchedulingStateFactory;

class AppointmentSchedulingServiceTest extends TestCase
{
    private AppointmentSchedulingService $service;

    private TriangulateHelper|MockInterface $mockTriangulateHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockTriangulateHelper = Mockery::mock(TriangulateHelper::class);
        $this->service = new AppointmentSchedulingService($this->mockTriangulateHelper);
    }

    /**
     * @test
     */
    public function it_schedule_high_priority_pending_services(): void
    {
        $pendingServices = PendingServiceFactory::many(3, [
            'nextServiceDate' => Carbon::today(),
            'previousAppointment' => AppointmentFactory::make([
                'initial' => true,
                'date' => Carbon::today()->subDays(40),
                'dateCompleted' => Carbon::today()->subDays(40),
            ]),
        ]);

        $scheduledRoute = ScheduledRouteFactory::make([
            'appointments' => [],
            'pendingServices' => [],
        ]);

        /** @var SchedulingState $schedulingState */
        $schedulingState = SchedulingStateFactory::make([
            'pendingServices' => collect($pendingServices),
            'scheduledRoutes' => collect([$scheduledRoute]),
        ]);

        $triangles = collect([
            new Triangle(0, 1, 2),
        ]);
        $this->mockTriangulateHelper
            ->shouldReceive('triangulate')
            ->once()
            ->andReturn($triangles);

        $schedulingState = $this->service->schedulePendingServices($schedulingState);

        /** @var ScheduledRoute $route */
        $route = $schedulingState->getScheduledRoutes()->first();
        $this->assertCount(3, $route->getPendingServices());
    }

    /**
     * @test
     */
    public function it_skips_scheduling_when_no_pending_service_found(): void
    {
        $scheduledRoute = ScheduledRouteFactory::make([
            'appointments' => [],
            'pendingServices' => [],
        ]);

        /** @var SchedulingState $schedulingState */
        $schedulingState = SchedulingStateFactory::make([
            'pendingServices' => collect(),
            'scheduledRoutes' => collect([$scheduledRoute]),
        ]);

        $this->mockTriangulateHelper
            ->shouldReceive('triangulate')
            ->never();

        $schedulingState = $this->service->schedulePendingServices($schedulingState);

        /** @var ScheduledRoute $route */
        $route = $schedulingState->getScheduledRoutes()->first();
        $this->assertCount(0, $route->getPendingServices());
    }
}
