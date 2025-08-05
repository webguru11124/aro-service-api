<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Tracking\Entities;

use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Domain\Tracking\Entities\ServicedRoute;
use App\Domain\Tracking\ValueObjects\RouteCompletionStats;
use App\Domain\Tracking\Entities\ScheduledAppointment;
use Carbon\Carbon;
use Mockery;
use PHPUnit\Framework\TestCase;
use Tests\Tools\Factories\Tracking\PlannedAppointmentFactory;

class ServicedRouteTest extends TestCase
{
    private ServicedRoute $servicedRoute;
    private ServicePro $servicePro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicePro = Mockery::mock(ServicePro::class);
        $this->servicedRoute = new ServicedRoute(
            1,
            $this->servicePro
        );
    }

    /**
     * @test
     */
    public function it_returns_expected_getters(): void
    {
        $this->assertEquals(1, $this->servicedRoute->getId());
        $this->assertSame($this->servicePro, $this->servicedRoute->getServicePro());
    }

    /**
     * @test
     */
    public function it_can_add_scheduled_appointment(): void
    {
        $appointment = Mockery::mock(ScheduledAppointment::class);
        $this->servicedRoute->addScheduledAppointment($appointment);

        $this->assertCount(1, $this->servicedRoute->getScheduledAppointments());
    }

    /**
     * @test
     */
    public function it_returns_correct_scheduled_appointments(): void
    {
        $appointment1 = Mockery::mock(ScheduledAppointment::class);
        $appointment2 = Mockery::mock(ScheduledAppointment::class);

        $this->servicedRoute->addScheduledAppointment($appointment1);
        $this->servicedRoute->addScheduledAppointment($appointment2);

        $scheduledAppointments = $this->servicedRoute->getScheduledAppointments();
        $this->assertCount(2, $scheduledAppointments);
        $this->assertSame($appointment1, $scheduledAppointments[0]);
        $this->assertSame($appointment2, $scheduledAppointments[1]);
    }

    /**
     * @test
     */
    public function it_returns_correct_completion_stats(): void
    {
        $plannedAppointment1 = PlannedAppointmentFactory::make(['id' => 1]);
        $plannedAppointment2 = PlannedAppointmentFactory::make(['id' => 2]);
        $plannedAppointment3 = PlannedAppointmentFactory::make(['id' => 3]);

        $completedAppointment1 = Mockery::mock(ScheduledAppointment::class);
        $completedAppointment1->shouldReceive('isComplete')->andReturn(true);
        $completedAppointment1->shouldReceive('getId')->andReturn(1);
        $completedAppointment1->shouldReceive('getDateComplete')->andReturn(Carbon::parse('2024-04-05 09:00:00'));
        $completedAppointment1->shouldReceive('getServiceTimeWindow')->andReturn(new TimeWindow(
            Carbon::parse('2024-04-05 09:00:00'),
            Carbon::parse('2024-04-05 10:00:00')
        ));

        $completedAppointment2 = Mockery::mock(ScheduledAppointment::class);
        $completedAppointment2->shouldReceive('isComplete')->andReturn(true);
        $completedAppointment2->shouldReceive('getId')->andReturn(2);
        $completedAppointment2->shouldReceive('getDateComplete')->andReturn(Carbon::parse('2024-04-05 10:00:00'));
        $completedAppointment2->shouldReceive('getServiceTimeWindow')->andReturn(new TimeWindow(
            Carbon::parse('2024-04-05 10:00:00'),
            Carbon::parse('2024-04-05 11:00:00')
        ));

        $completedAppointment3 = Mockery::mock(ScheduledAppointment::class);
        $completedAppointment3->shouldReceive('isComplete')->andReturn(true);
        $completedAppointment3->shouldReceive('getId')->andReturn(3);
        $completedAppointment3->shouldReceive('getDateComplete')->andReturn(Carbon::parse('2024-04-05 11:00:00'));
        $completedAppointment3->shouldReceive('getServiceTimeWindow')->andReturn(new TimeWindow(
            Carbon::parse('2024-04-05 11:00:00'),
            Carbon::parse('2024-04-05 12:00:00')
        ));

        $this->servicedRoute->addScheduledAppointment($completedAppointment1);
        $this->servicedRoute->addScheduledAppointment($completedAppointment2);
        $this->servicedRoute->addScheduledAppointment($completedAppointment3);

        $this->servicedRoute->addPlannedEvent($plannedAppointment1);
        $this->servicedRoute->addPlannedEvent($plannedAppointment2);
        $this->servicedRoute->addPlannedEvent($plannedAppointment3);

        $completionStats = $this->servicedRoute->getCompletionStats();

        $this->assertInstanceOf(RouteCompletionStats::class, $completionStats);
        $this->assertEquals(100, $completionStats->getCompletionPercentage());
        $this->assertEquals(180, $completionStats->getTotalServiceTime()->getTotalMinutes());
    }

    /**
     * @test
     */
    public function it_returns_correct_area_center(): void
    {
        $point1 = new Coordinate(5.2, 8.3);
        $point2 = new Coordinate(10.45, 14.45);

        $scheduledAppointment1 = Mockery::mock(ScheduledAppointment::class);
        $scheduledAppointment1->shouldReceive('getLocation')->andReturn($point1);

        $scheduledAppointment2 = Mockery::mock(ScheduledAppointment::class);
        $scheduledAppointment2->shouldReceive('getLocation')->andReturn($point2);

        $this->servicedRoute->addScheduledAppointment($scheduledAppointment1);
        $this->servicedRoute->addScheduledAppointment($scheduledAppointment2);

        $areaCenter = $this->servicedRoute->getAreaCenter();

        $expectedLatitude = ($point1->getLatitude() + $point2->getLatitude()) / 2;
        $expectedLongitude = ($point1->getLongitude() + $point2->getLongitude()) / 2;

        $this->assertInstanceOf(Coordinate::class, $areaCenter);
        $this->assertEquals($expectedLatitude, $areaCenter->getLatitude());
        $this->assertEquals($expectedLongitude, $areaCenter->getLongitude());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->servicedRoute);
        unset($this->servicePro);
    }
}
