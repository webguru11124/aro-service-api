<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Tracking\Entities;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\Tracking\Entities\Events\FleetRouteEvent;
use App\Domain\Tracking\Entities\FleetRoute;
use App\Domain\Tracking\ValueObjects\ConvexPolygon;
use App\Domain\Tracking\ValueObjects\RouteCompletionStats;
use App\Domain\Tracking\ValueObjects\RouteDrivingStats;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Tests\Tools\Factories\RouteStatsFactory;
use Tests\Tools\Factories\ServiceProFactory;
use Tests\Tools\Factories\Tracking\PlannedAppointmentFactory;
use Tests\Tools\Factories\Tracking\RouteCompletionStatsFactory;
use Tests\Tools\Factories\Tracking\RouteDrivingStatsFactory;
use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Carbon\CarbonInterval;
use Illuminate\Support\Collection;

class FleetRouteTest extends TestCase
{
    private Collection $route;
    private RouteDrivingStats $actualStats;
    private RouteCompletionStats $completionStats;

    protected function setUp(): void
    {
        parent::setUp();

        $this->route = collect([PlannedAppointmentFactory::make()]);
        $this->actualStats = RouteDrivingStatsFactory::make();
        $this->completionStats = RouteCompletionStatsFactory::make();
    }

    /**
     * @test
     */
    public function it_creates_tracking_fleet_route(): void
    {
        $mockedRouteData = $this->getMockRouteData();
        $fleetRoute = new FleetRoute(...$mockedRouteData);
        $fleetRoute->setRoute($this->route);
        $fleetRoute->setDrivingStats($this->actualStats);
        $fleetRoute->setCompletionStats($this->completionStats);

        $this->assertEquals($mockedRouteData['id'], $fleetRoute->getId());
        $this->assertEquals($mockedRouteData['startAt'], $fleetRoute->getStartAt());
        $this->assertEquals($mockedRouteData['servicePro'], $fleetRoute->getServicePro());
        $this->assertEquals($this->route, $fleetRoute->getRoute());
        $this->assertEquals($mockedRouteData['routeStats'], $fleetRoute->getRouteStats());
        $this->assertEquals($this->actualStats->toArray(), $fleetRoute->getDrivingStats()->toArray());
        $this->assertEquals($this->completionStats->toArray(), $fleetRoute->getCompletionStats()->toArray());
        $this->assertEquals($mockedRouteData['routeGeometry'], $fleetRoute->getRouteGeometry());
    }

    /**
     * @test
     */
    public function it_sets_route_correctly(): void
    {
        $fleetRoute = new FleetRoute(...$this->getMockRouteData());

        $fleetRoute->setRoute($this->route);

        $this->assertEquals($this->route, $fleetRoute->getRoute());
    }

    /**
     * @test
     */
    public function it_calculates_area_correctly(): void
    {
        $points = collect($this->getMockCollectionOfPoints());

        $routeEvents = $this->createMockRouteEvents($points);
        $fleetRoute = new FleetRoute(...$this->getMockRouteData());
        $fleetRoute->setRoute($routeEvents);
        $expectedArea = new ConvexPolygon($points);

        $this->assertEquals($expectedArea, $fleetRoute->getArea());
    }

    /**
     * @test
     */
    public function it_calculates_area_center_correctly(): void
    {
        $points = collect($this->getMockCollectionOfPoints());
        $routeEvents = $this->createMockRouteEvents($points);

        $fleetRoute = new FleetRoute(...$this->getMockRouteData());
        $fleetRoute->setRoute($routeEvents);

        $expectedCenter = new Coordinate(1.5, 1.5);

        $this->assertEquals($expectedCenter, $fleetRoute->getAreaCenter());
    }

    /**
     * @test
     */
    public function it_formats_stats_as_array_correctly(): void
    {
        $drivingStats = new RouteDrivingStats(
            'route_id',
            new Duration(CarbonInterval::minutes(120)),
            Distance::fromMeters(100),
            new Duration(CarbonInterval::minutes(60)),
            Distance::fromMeters(50),
            new Duration(CarbonInterval::minutes(180)),
            10.5,
            Distance::fromMeters(500),
            30.5
        );

        $completionStats = new RouteCompletionStats(
            1.2,
            20,
            new Duration(CarbonInterval::minutes(300)),
            false,
            85.5,
        );

        $fleetRoute = new FleetRoute(...$this->getMockRouteData());
        $fleetRoute->setDrivingStats($drivingStats);
        $fleetRoute->setCompletionStats($completionStats);
        $actualStatsArray = $fleetRoute->getStatsAsArray();

        $expectedStats = [
            'total_appointments' => 20,
            'total_service_time_minutes' => 300,
            'total_drive_time_minutes' => 120,
            'total_drive_miles' => 0.06,
            'average_drive_time_minutes' => 60,
            'average_drive_miles' => 0.03,
            'route_adherence' => 1.2,
        ];

        $this->assertEquals($expectedStats, $actualStatsArray);
    }

    private function getMockRouteData(): array
    {
        return   [
            'id' => 15,
            'startAt' => Carbon::now(),
            'servicePro' => ServiceProFactory::make(),
            'routeStats' => RouteStatsFactory::make(),
            'routeGeometry' => 'routeGeometryTest',
        ];
    }

    private function getMockCollectionOfPoints(): Collection
    {
        return collect([
            new Coordinate(1, 1),
            new Coordinate(2, 2),
        ]);
    }

    /**
     * Create mock FleetRouteEvent objects with specified locations.
     *
     * @param Collection $points The locations of route events.
     *
     * @return Collection A collection of mock FleetRouteEvent objects.
     */
    private function createMockRouteEvents(Collection $points): Collection
    {
        $routeEvents = new Collection();

        foreach ($points as $point) {
            $mockRouteEvent = $this->createMock(FleetRouteEvent::class);
            $mockRouteEvent->method('getLocation')->willReturn($point);
            $routeEvents->add($mockRouteEvent);
        }

        return $routeEvents;
    }
}
