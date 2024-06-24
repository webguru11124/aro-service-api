<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\PestRoutes;

use App\Domain\Tracking\Entities\FleetRoute;
use App\Domain\Tracking\Entities\FleetRouteState;
use App\Domain\Tracking\Services\RouteAdherenceCalculator;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentsDataProcessor;
use App\Infrastructure\Services\PestRoutes\PestRoutesRouteCompletionStatsService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\Tracking\FleetRouteFactory;
use Tests\Tools\Factories\Tracking\FleetRouteStateFactory;
use Tests\Tools\Factories\Tracking\PlannedAppointmentFactory;
use Tests\Tools\PestRoutesData\AppointmentData;
use Tests\Tools\TestValue;

class PestRoutesRouteCompletionStatsServiceTest extends TestCase
{
    private PestRoutesRouteCompletionStatsService $service;
    private AppointmentsDataProcessor|MockInterface $mockAppointmentsDataProcessor;
    private RouteAdherenceCalculator|MockInterface $mockRouteAdherenceCalculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockAppointmentsDataProcessor = Mockery::mock(AppointmentsDataProcessor::class);
        $this->mockRouteAdherenceCalculator = Mockery::mock(RouteAdherenceCalculator::class);
        $this->service = new PestRoutesRouteCompletionStatsService(
            $this->mockAppointmentsDataProcessor,
            $this->mockRouteAdherenceCalculator
        );
    }

    /**
     * @test
     */
    public function it_calculates_route_adherence_correctly(): void
    {
        $completedAppointmentsData = AppointmentData::getTestData(
            3,
            ['appointmentID' => 4, 'routeID' => 1, 'dateCompleted' => '2021-01-01 10:00:00', 'dateCancelled' => null],
            ['appointmentID' => 5, 'routeID' => 1, 'dateCompleted' => '2021-01-01 11:00:00', 'dateCancelled' => null],
            ['appointmentID' => 6, 'routeID' => 1, 'dateCompleted' => '2021-01-01 12:00:00', 'dateCancelled' => null]
        );

        $appointments = collect([
            PlannedAppointmentFactory::make(['id' => 4]),
            PlannedAppointmentFactory::make(['id' => 5]),
            PlannedAppointmentFactory::make(['id' => 6]),
        ]);

        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(collect($completedAppointmentsData));

        $expectedAdherence = 100.0;

        $this->mockRouteAdherenceCalculator
            ->shouldReceive('calculateRouteAdherence')
            ->withArgs(function ($optimizedIds, $completedIds) {
                return is_array($optimizedIds) && is_array($completedIds);
            })
            ->once()
            ->andReturn($expectedAdherence);

        $fleetRoute = FleetRouteFactory::make(['id' => 1, 'appointments' => $appointments]);
        /** @var FleetRouteState $fleetRouteState */
        $fleetRouteState = FleetRouteStateFactory::make([
            'fleetRoutes' => [$fleetRoute],
        ]);

        $this->service->updateCompletionStats($fleetRouteState);

        /** @var FleetRoute $route */
        $route = $fleetRouteState->getFleetRoutes()->first();
        $this->assertEquals($expectedAdherence, $route->getCompletionStats()->getRouteAdherence());
    }

    /**
     * @test
     */
    public function it_calculates_partial_route_adherence_correctly(): void
    {
        $appointmentsData = AppointmentData::getTestData(
            3,
            ['appointmentID' => 4, 'routeID' => 1, 'dateCompleted' => '2021-01-01 10:00:00', 'dateCancelled' => null],
            ['appointmentID' => 6, 'routeID' => 1, 'dateCompleted' => '2021-01-01 11:00:00', 'dateCancelled' => null],
            ['appointmentID' => 5, 'routeID' => 1, 'dateCompleted' => '2021-01-01 12:00:00', 'dateCancelled' => null]
        );

        $optimizedAppointments = collect([
            PlannedAppointmentFactory::make(['id' => 4]),
            PlannedAppointmentFactory::make(['id' => 5]),
            PlannedAppointmentFactory::make(['id' => 6]),
        ]);

        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(collect($appointmentsData));

        $expectedAdherence = 66.7;

        $this->mockRouteAdherenceCalculator
            ->shouldReceive('calculateRouteAdherence')
            ->withArgs(function ($optimizedIds, $completedIds) {
                return is_array($optimizedIds) && is_array($completedIds);
            })
            ->once()
            ->andReturn($expectedAdherence);

        $fleetRoute = FleetRouteFactory::make(['id' => 1, 'appointments' => $optimizedAppointments]);
        /** @var FleetRouteState $fleetRouteState */
        $fleetRouteState = FleetRouteStateFactory::make([
            'fleetRoutes' => [$fleetRoute],
        ]);

        $this->service->updateCompletionStats($fleetRouteState);

        /** @var FleetRoute $route */
        $route = $fleetRouteState->getFleetRoutes()->first();
        $this->assertEquals($expectedAdherence, round($route->getCompletionStats()->getRouteAdherence(), 1)); // Assuming 2 out of 3 are in the correct order
    }

    /**
     * @test
     */
    public function it_calculates_route_adherence_with_canceled_or_pending_appointments_correctly(): void
    {
        $officeId = TestValue::OFFICE_ID;

        $appointmentsData = AppointmentData::getTestData(
            3,
            ['appointmentID' => 4, 'routeID' => 1, 'dateCompleted' => '2021-01-01 10:00:00', 'dateCancelled' => null],
            ['appointmentID' => 5, 'routeID' => 1, 'dateCompleted' => null, 'dateCancelled' => null],
            ['appointmentID' => 6, 'routeID' => 1, 'dateCompleted' => '2021-01-01 12:00:00', 'dateCancelled' => null]
        );

        $optimizedAppointments = collect([
            PlannedAppointmentFactory::make(['id' => 4]),
            PlannedAppointmentFactory::make(['id' => 5]),
            PlannedAppointmentFactory::make(['id' => 6]),
            PlannedAppointmentFactory::make(['id' => 7]),
        ]);

        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(collect($appointmentsData));

        $expectedAdherence = 100.0;

        $this->mockRouteAdherenceCalculator
            ->shouldReceive('calculateRouteAdherence')
            ->withArgs(function ($optimizedIds, $completedIds) {
                return is_array($optimizedIds) && is_array($completedIds);
            })
            ->once()
            ->andReturn($expectedAdherence);

        $fleetRoute = FleetRouteFactory::make(['id' => 1, 'appointments' => $optimizedAppointments]);
        /** @var FleetRouteState $fleetRouteState */
        $fleetRouteState = FleetRouteStateFactory::make([
            'fleetRoutes' => [$fleetRoute],
        ]);

        $this->service->updateCompletionStats($fleetRouteState);

        /** @var FleetRoute $route */
        $route = $fleetRouteState->getFleetRoutes()->first();
        $this->assertEquals($expectedAdherence, $route->getCompletionStats()->getRouteAdherence());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->service);
        unset($this->mockAppointmentsDataProcessor);
        unset($this->mockRouteAdherenceCalculator);
    }
}
