<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Tracking\ValueObjects;

use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\Tracking\ValueObjects\RouteDrivingStats;
use Carbon\CarbonInterval;
use Tests\TestCase;

class RouteDrivingStatsTest extends TestCase
{
    private array $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'id' => 'test_id',
            'totalDriveTime' => new Duration(CarbonInterval::hours(2)),
            'totalDriveDistance' => Distance::fromMeters(150.5),
            'averageDriveTimeBetweenServices' => new Duration(CarbonInterval::hour()),
            'averageDriveDistanceBetweenServices' => Distance::fromMeters(75.25),
            'totalWorkingTime' => new Duration(CarbonInterval::hours(8)),
            'fuelConsumption' => 25.5,
            'historicVehicleMileage' => Distance::fromMeters(5000.75),
            'historicFuelConsumption' => 300.75,
        ];
    }

    /**
     * @test
     */
    public function it_returns_expected_getters(): void
    {
        $routeStats = new RouteDrivingStats(...$this->data);

        $this->assertEquals($this->data['id'], $routeStats->getId());
        $this->assertEquals($this->data['totalDriveTime'], $routeStats->getTotalDriveTime());
        $this->assertEquals($this->data['totalDriveDistance'], $routeStats->getTotalDriveDistance());
        $this->assertEquals($this->data['averageDriveTimeBetweenServices'], $routeStats->getAverageDriveTimeBetweenServices());
        $this->assertEquals($this->data['averageDriveDistanceBetweenServices'], $routeStats->getAverageDriveDistanceBetweenServices());
        $this->assertEquals($this->data['totalWorkingTime'], $routeStats->getTotalWorkingTime());
        $this->assertEquals($this->data['fuelConsumption'], $routeStats->getFuelConsumption());
        $this->assertEquals($this->data['historicVehicleMileage'], $routeStats->getHistoricVehicleMileage());
        $this->assertEquals($this->data['historicFuelConsumption'], $routeStats->getHistoricFuelConsumption());
    }

    /**
     * @test
     */
    public function it_correctly_converts_to_array(): void
    {
        $routeStats = new RouteDrivingStats(...$this->data);

        $expectedArray = [
            'total_drive_time_minutes' => $this->data['totalDriveTime']->getTotalMinutes(),
            'total_drive_miles' => $this->data['totalDriveDistance']->getMiles(),
            'average_drive_time_minutes' => $this->data['averageDriveTimeBetweenServices']->getTotalMinutes(),
            'average_drive_miles' => $this->data['averageDriveDistanceBetweenServices']->getMiles(),
            'total_working_time_minutes' => $this->data['totalWorkingTime']->getTotalMinutes(),
            'fuel_consumption' => $this->data['fuelConsumption'],
            'historic_vehicle_mileage' => $this->data['historicVehicleMileage']->getMiles(),
            'historic_fuel_consumption' => $this->data['historicFuelConsumption'],
        ];

        $this->assertEquals($expectedArray, $routeStats->toArray());
    }
}
