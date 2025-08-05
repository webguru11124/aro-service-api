<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Scheduling\Entities;

use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\ValueObjects\RouteType;
use App\Domain\Scheduling\Entities\Appointment;
use App\Domain\Scheduling\Entities\ScheduledRoute;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Tests\TestCase;
use Tests\Tools\Factories\Scheduling\AppointmentFactory;
use Tests\Tools\Factories\Scheduling\PendingServiceFactory;
use Tests\Tools\Factories\Scheduling\ServicePointFactory;
use Tests\Tools\Factories\ServiceProFactory;
use Tests\Tools\TestValue;

class ScheduledRouteTest extends TestCase
{
    private int $id;
    private int $officeId;
    private CarbonInterface $date;
    private ServicePro $servicePro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicePro = ServiceProFactory::make([
            'id' => TestValue::EMPLOYEE_ID,
            'name' => 'John Doe',
            'startLocation' => new Coordinate(TestValue::MIN_LATITUDE, TestValue::MIN_LONGITUDE),
        ]);

        $this->id = $this->faker->randomNumber(3);
        $this->officeId = $this->faker->randomNumber(3);
        $this->date = Carbon::createFromDate($this->faker->dateTime());
    }

    /**
     * @test
     */
    public function it_returns_correct_values(): void
    {
        $scheduledRoute = $this->buildScheduledRoute();

        $this->assertEquals($this->id, $scheduledRoute->getId());
        $this->assertEquals($this->officeId, $scheduledRoute->getOfficeId());
        $this->assertEquals($this->date, $scheduledRoute->getDate());
        $this->assertEquals($this->servicePro, $scheduledRoute->getServicePro());
        $this->assertEquals(RouteType::REGULAR_ROUTE, $scheduledRoute->getRouteType());
    }

    /**
     * @test
     *
     * ::buildCluster
     */
    public function it_builds_cluster(): void
    {
        Carbon::setTestNow($this->date);

        $scheduledRoute = $this->buildScheduledRoute();

        $servePoint = ServicePointFactory::make([
            'location' => new Coordinate(TestValue::LATITUDE, TestValue::LONGITUDE),
        ]);
        $cluster = $scheduledRoute->buildCluster();

        $this->assertEquals($this->id, $cluster->getId());
        $this->assertEquals($scheduledRoute->getCapacity(), $cluster->getCapacity());
        $this->assertEquals(36.42, $cluster->getDistanceToServicePoint($servePoint)->getMiles());
    }

    /**
     * @test
     *
     * @dataProvider capacitiesBasedOnDateDataProvider
     *
     * ::getCapacity
     */
    public function it_returns_capacity(CarbonInterface $date, int $expectedCapacities): void
    {
        $scheduledRoute = $this->buildScheduledRoute($date);

        $scheduledRoute->addAppointment(AppointmentFactory::make());
        $scheduledRoute->addPendingService(PendingServiceFactory::make());

        $this->assertEquals($expectedCapacities, $scheduledRoute->getCapacity());
    }

    public static function capacitiesBasedOnDateDataProvider(): iterable
    {
        $current = Carbon::now();

        yield 'Today' => [$current, 14];
        yield 'One day before' => [$current->clone()->subDay(), 14];
        yield 'Two days before' => [$current->clone()->subDays(2), 13];
        yield 'Three days before' => [$current->clone()->subDays(3), 11];
        yield 'Four days before' => [$current->clone()->subDays(4), 11];
    }

    /**
     * @test
     *
     * ::getCapacity
     */
    public function it_returns_zero_capacity_when_route_overbooked(): void
    {
        $scheduledRoute = $this->buildScheduledRoute(Carbon::tomorrow());

        $appointments = collect(AppointmentFactory::many(50));
        $appointments->each(
            fn (Appointment $appointment) => $scheduledRoute->addAppointment($appointment)
        );

        $this->assertEquals(0, $scheduledRoute->getCapacity());
    }

    /**
     * @test
     *
     * ::getCapacity
     */
    public function it_returns_zero_capacity_when_service_pro_has_no_skills(): void
    {
        $servicePro = ServiceProFactory::make([
            'skills' => [],
        ]);
        $scheduledRoute = new ScheduledRoute(
            $this->id,
            $this->officeId,
            Carbon::tomorrow(),
            $servicePro,
            routeType: RouteType::REGULAR_ROUTE,
            actualCapacityCount: 16,
        );

        $appointments = collect(AppointmentFactory::many(5));
        $appointments->each(
            fn (Appointment $appointment) => $scheduledRoute->addAppointment($appointment)
        );

        $this->assertEquals(0, $scheduledRoute->getCapacity());
    }

    /**
     * @test
     *
     * ::addAppointment
     * ::getAppointments
     */
    public function it_adds_appointment(): void
    {
        $scheduledRoute = $this->buildScheduledRoute();

        $scheduledRoute->addAppointment(AppointmentFactory::make());
        $scheduledRoute->addAppointment(AppointmentFactory::make());

        $this->assertCount(2, $scheduledRoute->getAppointments());
    }

    /**
     * @test
     *
     * ::removeAppointment
     */
    public function it_removes_appointment(): void
    {
        $appointment = AppointmentFactory::make([
            'id' => TestValue::APPOINTMENT_ID,
        ]);

        $scheduledRoute = $this->buildScheduledRoute();

        $scheduledRoute->addAppointment($appointment);
        $scheduledRoute->addAppointment(AppointmentFactory::make());
        $scheduledRoute->removeAppointment(TestValue::APPOINTMENT_ID);

        $this->assertCount(1, $scheduledRoute->getAppointments());
    }

    /**
     * @test
     *
     * ::addPendingService
     * ::getPendingServices
     */
    public function it_adds_pending_service(): void
    {
        $scheduledRoute = $this->buildScheduledRoute();

        $scheduledRoute->addPendingService(PendingServiceFactory::make());
        $scheduledRoute->addPendingService(PendingServiceFactory::make());

        $this->assertCount(2, $scheduledRoute->getPendingServices());
    }

    /**
     * @return ScheduledRoute
     */
    private function buildScheduledRoute(CarbonInterface|null $date = null): ScheduledRoute
    {
        return new ScheduledRoute(
            id: $this->id,
            officeId: $this->officeId,
            date: $date ?? $this->date,
            servicePro: $this->servicePro,
            routeType: RouteType::REGULAR_ROUTE,
            actualCapacityCount: 22,
        );
    }
}
