<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Tracking\Entities;

use App\Domain\Tracking\Entities\FleetRouteState;
use PHPUnit\Framework\TestCase;
use Tests\Tools\Factories\OptimizationStateMetricsFactory;
use Tests\Tools\Factories\RouteStatsFactory;
use Tests\Tools\Factories\Tracking\FleetRouteFactory;
use Tests\Tools\Factories\Tracking\FleetRouteStateFactory;
use Tests\Tools\Factories\Tracking\RouteCompletionStatsFactory;
use Tests\Tools\Factories\Tracking\RouteDrivingStatsFactory;

class FleetRouteStateTest extends TestCase
{
    /**
     * @test
     */
    public function it_calculates_summary_correctly(): void
    {
        /** @var FleetRouteState $fleetRouteState */
        $fleetRouteState = FleetRouteStateFactory::make([
            'fleetRoutes' => [
                FleetRouteFactory::make([
                    'id' => 1,
                    'routeStats' => RouteStatsFactory::make([
                        'totalAppointments' => 6,
                        'totalDriveTime' => 50,
                        'totalDriveDistance' => 1609.34,
                        'totalServiceTime' => 30,
                    ]),
                    'drivingStats' => RouteDrivingStatsFactory::make([
                        'fuelConsumption' => 2.5,
                        'totalDriveTime' => 60,
                        'totalDriveDistance' => 1609.34,
                        'historicFuelConsumption' => 20,
                        'historicVehicleMileage' => 10,
                    ]),
                    'completionStats' => RouteCompletionStatsFactory::make([
                        'totalAppointments' => 6,
                        'totalServiceTime' => 45,
                    ]),
                ]),
                FleetRouteFactory::make([
                    'id' => 2,
                    'routeStats' => RouteStatsFactory::make([
                        'totalAppointments' => 4,
                        'totalDriveTime' => 70,
                        'totalDriveDistance' => 1609.34,
                        'totalServiceTime' => 50,
                    ]),
                    'drivingStats' => RouteDrivingStatsFactory::make([
                        'fuelConsumption' => 2.5,
                        'totalDriveTime' => 30,
                        'totalDriveDistance' => 2000,
                        'historicFuelConsumption' => 20,
                        'historicVehicleMileage' => 10,
                    ]),
                    'completionStats' => RouteCompletionStatsFactory::make([
                        'totalAppointments' => 3,
                        'totalServiceTime' => 25,
                    ]),
                ]),
            ],
        ]);

        $summary = $fleetRouteState->getSummary();

        $this->assertEquals(2, $summary->getTotalRoutes());
        $this->assertEquals(10, $summary->getTotalAppointments());
        $this->assertEquals(120, $summary->getTotalDriveTimeMinutes());
        $this->assertEquals(2.0, $summary->getTotalDriveMiles());
        $this->assertEquals(80, $summary->getTotalServiceTimeMinutes());
        $this->assertEquals(2.5, $summary->getAppointmentsPerGallon());

        $this->assertEquals(2, $summary->getTotalRoutesActual());
        $this->assertEquals(9, $summary->getTotalAppointmentsActual());
        $this->assertEquals(90, $summary->getTotalDriveTimeMinutesActual());
        $this->assertEquals(2.24, $summary->getTotalDriveMilesActual());
        $this->assertEquals(70, $summary->getTotalServiceTimeMinutesActual());
        $this->assertEquals(1.8, $summary->getAppointmentsPerGallonActual());
    }

    /**
     * @test
     */
    public function it_returns_fleet_route_by_id(): void
    {
        /** @var FleetRouteState $fleetRouteState */
        $fleetRouteState = FleetRouteStateFactory::make([
            'fleetRoutes' => [
                FleetRouteFactory::make(['id' => 1]),
                FleetRouteFactory::make(['id' => 2]),
            ],
        ]);

        $fleetRoute = $fleetRouteState->getFleetRouteById(1);

        $this->assertEquals(1, $fleetRoute->getId());
    }

    /**
     * @test
     */
    public function it_adds_fleet_route_correctly(): void
    {
        $fleetRoute = FleetRouteFactory::make();
        $fleetRouteState = FleetRouteStateFactory::make();
        $fleetRouteState->addFleetRoute($fleetRoute);

        $this->assertTrue($fleetRouteState->getFleetRoutes()->contains($fleetRoute));
    }

    /**
     * @test
     */
    public function it_returns_expected_value_for_can_update_stats(): void
    {
        $fleetRouteStateToday = FleetRouteStateFactory::make(['date' => now()]);
        $this->assertTrue($fleetRouteStateToday->canUpdateStats());

        $updatedAt = now()->subDay();
        $fleetRouteStateSameDay = FleetRouteStateFactory::make(['date' => $updatedAt, 'updatedAt' => $updatedAt]);
        $this->assertTrue($fleetRouteStateSameDay->canUpdateStats());

        $fleetRouteStateFuture = FleetRouteStateFactory::make(['date' => now()->addDays(2)]);
        $this->assertFalse($fleetRouteStateFuture->canUpdateStats());
    }

    /**
     * @test
     */
    public function it_returns_null_when_fleet_route_not_found_by_id(): void
    {
        $fleetRouteState = FleetRouteStateFactory::make(['fleetRoutes' => [FleetRouteFactory::make()]]);

        $retrievedFleetRoute = $fleetRouteState->getFleetRouteById(999);

        $this->assertNull($retrievedFleetRoute);
    }

    /**
     * @test
     */
    public function it_returns_expected_values(): void
    {
        $id = 1;
        $officeId = 2;
        $date = now();
        $updatedAt = now();
        $metrics = OptimizationStateMetricsFactory::make();

        $fleetRouteState = FleetRouteStateFactory::make([
            'id' => $id,
            'officeId' => $officeId,
            'date' => $date,
            'updatedAt' => $updatedAt,
            'metrics' => $metrics,
        ]);

        $this->assertEquals($id, $fleetRouteState->getId());
        $this->assertEquals($officeId, $fleetRouteState->getOfficeId());
        $this->assertSame($date, $fleetRouteState->getDate());
        $this->assertSame($updatedAt, $fleetRouteState->getUpdatedAt());
        $this->assertSame($metrics, $fleetRouteState->getMetrics());
    }
}
