<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Tracking\Factories;

use App\Domain\RouteOptimization\Factories\RouteStatsFactory;
use App\Domain\Tracking\Entities\FleetRoute;
use App\Domain\Tracking\Factories\FleetRouteFactory;
use App\Domain\Tracking\Factories\RouteFactory;
use App\Domain\Tracking\Factories\ServiceProFactory;
use Carbon\CarbonTimeZone;
use PHPUnit\Framework\TestCase;
use Tests\Tools\Factories\Tracking\PlannedAppointmentFactory;
use Tests\Tools\TestValue;
use Tests\Traits\RouteStatsData;

class FleetRouteFactoryTest extends TestCase
{
    use RouteStatsData;

    private FleetRouteFactory $fleetRouteFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $routeFactory = \Mockery::mock(RouteFactory::class);
        $routeStatsFactory = \Mockery::mock(RouteStatsFactory::class);
        $serviceProFactory = \Mockery::mock(ServiceProFactory::class);

        $routeFactory->shouldReceive('create')->andReturn(collect(PlannedAppointmentFactory::many(3)));
        $routeStatsFactory->shouldReceive('create')->andReturn(\Tests\Tools\Factories\RouteStatsFactory::make());
        $serviceProFactory->shouldReceive('create')->andReturn(\Tests\Tools\Factories\ServiceProFactory::make());

        $this->fleetRouteFactory = new FleetRouteFactory(
            $routeFactory,
            $routeStatsFactory,
            $serviceProFactory
        );
    }

    /**
     * @test
     */
    public function it_creates_a_fleet_route(): void
    {
        $stateData = $this->getRouteData();
        $fleetRoute = $this->fleetRouteFactory->create($stateData, CarbonTimeZone::create(TestValue::TIME_ZONE));

        $this->assertInstanceOf(FleetRoute::class, $fleetRoute);
    }

    /**
     * @test
     */
    public function it_does_not_creates_fleet_route_for_reschedule_route(): void
    {
        $stateData = $this->getRouteData();
        $stateData['service_pro']['name'] = '#Reschedule Route#';
        $fleetRoute = $this->fleetRouteFactory->create($stateData, CarbonTimeZone::create(TestValue::TIME_ZONE));

        $this->assertNull($fleetRoute);
    }

    /**
     * @return mixed[]
     */
    private function getRouteData(): array
    {
        return [
            'optimization_state_id' => 10000,
            'route_id' => 4497004,
            'route_stats' => json_decode('{"total_regular": 20, "total_initials": 1, "total_reservice": 0, "total_drive_miles": 55.75, "total_appointments": 21, "average_drive_miles": 1.49, "total_weighted_services": 22, "total_break_time_minutes": 60, "total_drive_time_minutes": 115, "average_drive_time_minutes": 3, "total_service_time_minutes": 512, "total_working_time_minutes": 590}', true),
            'actual_stats' => json_decode('{"total_drive_miles": 60.05, "average_drive_miles": 4.35, "total_drive_time_minutes": 139, "average_drive_time_minutes": 10, "total_working_time_minutes": 566, "fuel_consumption": 5.6, "route_adherence": 80.5}', true),
            'schedule' => json_decode('[{"location": {"lat": 30.351305579189788, "lon": -97.70943845704998}, "description": "Start", "work_event_type": "Start Location", "scheduled_time_window": {"end": "2024-03-11 07:30:00", "start": "2024-03-11 07:30:00"}}]', true),
            'service_pro' => json_decode('{"id": 530772, "name": "ARO QA7", "workday_id": "", "working_hours": {"end_at": "18:30:00", "start_at": "08:00:00"}}', true),
            'details' => json_decode('{"end_at": "2024-03-11 14:06:21", "capacity": 10, "start_at": "2024-03-11 07:30:00", "route_type": "Short Route", "end_location": {"lat": 30.351, "lon": -97.709}, "start_location": {"lat": 30.351305579189788, "lon": -97.70943845704998}, "optimization_score": 0.71}', true),
            'metrics' => null,
            'geometry' => null,
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->fleetRouteFactory);
    }
}
