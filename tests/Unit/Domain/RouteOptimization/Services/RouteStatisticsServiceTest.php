<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Services;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Services\RouteStatisticsService;
use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\TravelFactory;
use Tests\Tools\Factories\WorkBreakFactory;

class RouteStatisticsServiceTest extends TestCase
{
    private RouteStatisticsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new RouteStatisticsService();
    }

    /**
     * @test
     */
    public function it_calculates_statistics_correctly(): void
    {
        $result = $this->service->getStats($this->makeRoute());

        $this->assertEquals(30, $result->getTotalBreakTime()->getTotalMinutes());
        $this->assertEquals(1, $result->getTotalInitials());
        $this->assertEquals(1, $result->getTotalReservice());
        $this->assertEquals(2, $result->getTotalRegular());
        $this->assertEquals(4, $result->getTotalAppointments());
        $this->assertEquals(5, $result->getTotalWeightedServices());
        $this->assertEquals(2400, $result->getTotalDriveTime()->getTotalSeconds());
        $this->assertEquals(5000, $result->getTotalDriveDistance()->getMeters());
        $this->assertEquals(2500, $result->getAverageDriveDistanceBetweenServices()->getMeters());
        $this->assertEquals(1200, $result->getAverageDriveTimeBetweenServices()->getTotalSeconds());
        $this->assertEquals(4440, $result->getTotalServiceTime()->getTotalSeconds());
        $this->assertEquals(8640, $result->getTotalWorkingTime()->getTotalSeconds());
    }

    /**
     * @test
     */
    public function it_returns_route_summary(): void
    {
        $date = Carbon::now();
        $result = $this->service->getRouteSummary($this->makeRoute(), $date);

        $this->assertEquals(6840, $result->totalWorkingTime->getTotalSeconds());
        $this->assertEquals(4440, $result->servicingTime->getTotalSeconds());
        $this->assertEquals(3600, $result->drivingTime->getTotalSeconds());
        $this->assertFalse($result->excludeFirstAppointment);
        $this->assertSame($date, $result->asOf);
    }

    private function makeRoute(): Route
    {
        $travels = [
            TravelFactory::make([
                'distance' => Distance::fromMeters(1000),
                'timeWindow' => new TimeWindow(
                    Carbon::now()->setTimeFromTimeString('08:00:00'),
                    Carbon::now()->setTimeFromTimeString('08:10:00'),
                ),
            ]),
            TravelFactory::make([
                'distance' => Distance::fromMeters(2000),
                'timeWindow' => new TimeWindow(
                    Carbon::now()->setTimeFromTimeString('09:00:00'),
                    Carbon::now()->setTimeFromTimeString('09:20:00'),
                ),
            ]),
            TravelFactory::make([
                'distance' => Distance::fromMeters(3000),
                'timeWindow' => new TimeWindow(
                    Carbon::now()->setTimeFromTimeString('12:00:00'),
                    Carbon::now()->setTimeFromTimeString('12:20:00'),
                ),
            ]),
            TravelFactory::make([
                'distance' => Distance::fromMeters(4000),
                'timeWindow' => new TimeWindow(
                    Carbon::now()->setTimeFromTimeString('16:40:00'),
                    Carbon::now()->setTimeFromTimeString('16:50:00'),
                ),
            ]),
        ];

        $appointments = [
            AppointmentFactory::make(['description' => 'initial appointment']),
            AppointmentFactory::make(['description' => 'reservice appointment']),
            AppointmentFactory::make(['description' => 'regular appointment']),
            AppointmentFactory::make(['description' => 'regular appointment']),
        ];

        $workBreaks = [
            WorkBreakFactory::make(),
            WorkBreakFactory::make(),
        ];

        return RouteFactory::make([
            'workEvents' => array_merge(
                $appointments,
                $workBreaks,
                $travels
            ),
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->service);
    }
}
