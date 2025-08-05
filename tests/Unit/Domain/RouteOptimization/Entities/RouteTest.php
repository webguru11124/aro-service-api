<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Entities;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkEvent;
use App\Domain\RouteOptimization\Enums\MetricKey;
use App\Domain\RouteOptimization\ValueObjects\RouteConfig;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Metric;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Score;
use App\Domain\RouteOptimization\ValueObjects\RouteType;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\EndLocationFactory;
use Tests\Tools\Factories\LunchFactory;
use Tests\Tools\Factories\ReservedTimeFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\RouteOptimization\MeetingFactory;
use Tests\Tools\Factories\ServiceProFactory;
use Tests\Tools\Factories\StartLocationFactory;
use Tests\Tools\Factories\TotalWeightedServiceMetricFactory;
use Tests\Tools\Factories\TravelFactory;
use Tests\Tools\Factories\WorkBreakFactory;
use Tests\Tools\TestValue;

class RouteTest extends TestCase
{
    private ServicePro $servicePro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicePro = ServiceProFactory::make();
    }

    /**
     * @test
     */
    public function create_route(): void
    {
        $servicePro = ServiceProFactory::make();
        $appointment = AppointmentFactory::make();
        $workBreak = WorkBreakFactory::make();
        $reservedTime = ReservedTimeFactory::make();
        $date = Carbon::now();

        $route = new Route(
            id: TestValue::ROUTE_ID,
            officeId: $this->faker->randomNumber(2),
            date: $date,
            servicePro: $servicePro,
            routeType: RouteType::REGULAR_ROUTE,
            actualCapacityCount: 21,
            config: new RouteConfig(2, 1, 3),
        );
        $route->addWorkEvent($workBreak);
        $route->addWorkEvent($appointment);
        $route->addWorkEvent($reservedTime);

        $this->assertEquals(TestValue::ROUTE_ID, $route->getId());
        $this->assertSame($servicePro, $route->getServicePro());
        $this->assertSame($date, $route->getDate());

        $this->assertEquals([$appointment], $route->getAppointments()->values()->all());
        $this->assertEquals([$workBreak], $route->getWorkBreaks()->values()->all());
        $this->assertEquals([], $route->getLunch()->values()->all());
        $this->assertEquals([$workBreak, $reservedTime], $route->getAllBreaks()->values()->all());
        $this->assertEquals([$reservedTime], $route->getReservedTimes()->values()->all());
        $this->assertEquals(2, $route->getConfig()->getInsideSales());
        $this->assertEquals(1, $route->getConfig()->getSummary());
        $this->assertEquals(3, $route->getConfig()->getBreaks());
    }

    /**
     * @test
     *
     * ::clearWorkEvents
     */
    public function it_clears_work_events(): void
    {
        /** @var Route $route */
        $route = RouteFactory::make();
        $this->assertNotEmpty($route->getWorkEvents());
        $route->clearWorkEvents();
        $this->assertEmpty($route->getWorkEvents());
    }

    /**
     * @test
     *
     * ::getMaxCapacity
     */
    public function it_returns_max_capacity_based_on_route_type(): void
    {
        $route01 = RouteFactory::make([
            'routeType' => RouteType::REGULAR_ROUTE,
            'actualCapacityCount' => 22,
            'config' => new RouteConfig(2, 1),
        ]);
        $route02 = RouteFactory::make([
            'routeType' => RouteType::EXTENDED_ROUTE,
            'actualCapacityCount' => 24,
            'config' => new RouteConfig(2, 0),
        ]);
        $route03 = RouteFactory::make([
            'routeType' => RouteType::SHORT_ROUTE,
            'actualCapacityCount' => 15,
            'config' => new RouteConfig(1, 1),
        ]);

        $this->assertEquals(16, $route01->getMaxCapacity());
        $this->assertEquals(19, $route02->getMaxCapacity());
        $this->assertEquals(10, $route03->getMaxCapacity());
    }

    /**
     * @test
     *
     * ::removeWorkBreaks
     */
    public function it_removes_all_work_breaks(): void
    {
        $route = $this->buildRouteWithWorkEvents([
            AppointmentFactory::make(),
            WorkBreakFactory::make(),
            AppointmentFactory::make(),
            LunchFactory::make(),
            AppointmentFactory::make(),
            WorkBreakFactory::make(),
            AppointmentFactory::make(),
        ]);

        $route->removeWorkBreaks();

        $this->assertTrue($route->getWorkBreaks()->isEmpty());
        $this->assertEquals(4, $route->getWorkEvents()->count());
    }

    /**
     * @test
     *
     * ::getWorkEvents
     */
    public function it_returns_work_events(): void
    {
        $route = $this->buildRouteWithWorkEvents([
            AppointmentFactory::make(),
            TravelFactory::make(),
            WorkBreakFactory::make(),
            AppointmentFactory::make(),
            LunchFactory::make(),
            AppointmentFactory::make(),
        ]);

        $this->assertCount(6, $route->getWorkEvents());
    }

    /**
     * @test
     *
     * ::getMeetings
     */
    public function it_returns_meetings(): void
    {
        $route = $this->buildRouteWithWorkEvents([
            AppointmentFactory::make(),
            MeetingFactory::make(),
        ]);

        $this->assertCount(1, $route->getMeetings());
    }

    /**
     * @test
     *
     * ::getTravelEvents
     */
    public function it_returns_travel_events(): void
    {
        $route = $this->buildRouteWithWorkEvents([
            AppointmentFactory::make(),
            TravelFactory::make(),
            WorkBreakFactory::make(),
            AppointmentFactory::make(),
            LunchFactory::make(),
            TravelFactory::make(),
            AppointmentFactory::make(),
        ]);

        $this->assertCount(2, $route->getTravelEvents());
    }

    /**
     * @test
     *
     * ::addWorkEvent
     */
    public function it_adds_work_event(): void
    {
        $route = $this->buildRouteWithWorkEvents([
            AppointmentFactory::make(),
        ]);

        $route->addWorkEvent(
            WorkBreakFactory::make()
        );

        $this->assertCount(2, $route->getWorkEvents());

        /** @var WorkBreak $workBreak */
        $workBreak = $route->getWorkBreaks()->first();
        $this->assertEquals($route->getId(), $workBreak->getRouteId());
    }

    /**
     * @test
     *
     * ::getTimeWindow
     */
    public function it_returns_time_window(): void
    {
        $route = $this->buildRouteWithWorkEvents([
            StartLocationFactory::make(),
            EndLocationFactory::make(),
        ]);

        $this->assertEquals(720, $route->getTimeWindow()->getTotalMinutes());
    }

    /**
     * @test
     *
     * ::setTimeWindow
     */
    public function it_sets_time_window(): void
    {
        $route = $this->buildRouteWithWorkEvents([
            StartLocationFactory::make(),
            EndLocationFactory::make(),
        ]);

        $testTimeWindow = new TimeWindow(
            Carbon::now()->setTimeFromTimeString('09:00:00'),
            Carbon::now()->setTimeFromTimeString('17:00:00'),
        );
        $route->setTimeWindow($testTimeWindow);

        $this->assertEquals(480, $route->getTimeWindow()->getTotalMinutes());
        $this->assertEquals($testTimeWindow->getStartAt(), $route->getTimeWindow()->getStartAt());
        $this->assertEquals($testTimeWindow->getEndAt(), $route->getTimeWindow()->getEndAt());
    }

    /**
     * @test
     *
     * ::getStartLocation
     */
    public function it_returns_start_location_of_route(): void
    {
        $startLocation = StartLocationFactory::make();
        $route = $this->buildRouteWithWorkEvents([
            $startLocation,
        ]);

        $result = $route->getStartLocation();

        $this->assertEquals($startLocation, $result);
    }

    /**
     * @test
     *
     * ::getStartLocation
     */
    public function it_returns_start_location_of_service_pro_home_when_no_start_location_object_added(): void
    {
        $serviceProLocation = new Coordinate(
            $this->faker->latitude,
            $this->faker->longitude,
        );
        $serviceProWorkingHours = new TimeWindow(
            Carbon::now()->setTimeFromTimeString('09:30:00'),
            Carbon::now()->setTimeFromTimeString('18:30:00'),
        );
        $this->servicePro = ServiceProFactory::make([
            'startLocation' => $serviceProLocation,
            'workingHours' => $serviceProWorkingHours,
        ]);
        $route = $this->buildRouteWithWorkEvents();

        $result = $route->getStartLocation();

        $this->assertEquals($serviceProWorkingHours->getStartAt(), $result->getTimeWindow()->getStartAt());
        $this->assertEquals($serviceProLocation, $result->getLocation());
    }

    /**
     * @test
     *
     * ::getEndLocation
     */
    public function it_returns_end_location_of_route(): void
    {
        $endLocation = EndLocationFactory::make();
        $route = $this->buildRouteWithWorkEvents([
            $endLocation,
        ]);

        $result = $route->getEndLocation();

        $this->assertEquals($endLocation, $result);
    }

    /**
     * @test
     *
     * ::getEndLocation
     */
    public function it_returns_end_location_of_service_pro_home_when_no_end_location_object_added(): void
    {
        $serviceProLocation = new Coordinate(
            $this->faker->latitude,
            $this->faker->longitude,
        );
        $serviceProWorkingHours = new TimeWindow(
            Carbon::now()->setTimeFromTimeString('09:30:00'),
            Carbon::now()->setTimeFromTimeString('18:30:00'),
        );
        $this->servicePro = ServiceProFactory::make([
            'endLocation' => $serviceProLocation,
            'workingHours' => $serviceProWorkingHours,
        ]);
        $route = $this->buildRouteWithWorkEvents();

        $result = $route->getEndLocation();

        $this->assertEquals($serviceProWorkingHours->getEndAt(), $result->getTimeWindow()->getEndAt());
        $this->assertEquals($serviceProLocation, $result->getLocation());
    }

    /**
     * @test
     *
     * ::setStartLocationCoordinatesToServiceProHome
     */
    public function it_sets_start_location_coordinates_to_service_pro_home(): void
    {
        $serviceProLocation = new Coordinate(
            $this->faker->latitude,
            $this->faker->longitude,
        );
        $this->servicePro = ServiceProFactory::make([
            'startLocation' => $serviceProLocation,
        ]);
        $route = $this->buildRouteWithWorkEvents([
            StartLocationFactory::make(),
            EndLocationFactory::make(),
        ]);

        $route->setStartLocationCoordinatesToServiceProHome();

        $this->assertEquals($serviceProLocation, $route->getStartLocation()->getLocation());
    }

    /**
     * @test
     *
     * ::setEndLocationCoordinatesToServiceProHome
     */
    public function it_sets_end_location_coordinates_to_service_pro_home(): void
    {
        $serviceProLocation = new Coordinate(
            $this->faker->latitude,
            $this->faker->longitude,
        );
        $this->servicePro = ServiceProFactory::make([
            'endLocation' => $serviceProLocation,
        ]);
        $route = $this->buildRouteWithWorkEvents([
            StartLocationFactory::make(),
            EndLocationFactory::make(),
        ]);

        $route->setEndLocationCoordinatesToServiceProHome();

        $this->assertEquals($serviceProLocation, $route->getEndLocation()->getLocation());
    }

    /**
     * @test
     *
     * ::setupRouteStart
     */
    public function it_setups_route_start(): void
    {
        $location = new Coordinate(
            $this->faker->latitude,
            $this->faker->longitude,
        );

        $startAt = Carbon::now()->setTimeFromTimeString('12:15:00');

        $route = $this->buildRouteWithWorkEvents([
            StartLocationFactory::make(),
        ]);

        $route->setupRouteStart($startAt, $location);

        $this->assertEquals($location, $route->getStartLocation()->getLocation());
        $this->assertEquals($startAt, $route->getStartLocation()->getTimeWindow()->getStartAt());
    }

    /**
     * @test
     */
    public function it_sorts_work_events_by_start_time(): void
    {
        $workEvents = [
            WorkBreakFactory::make([
                'id' => 8,
                'timeWindow' => new TimeWindow(
                    Carbon::tomorrow()->hour(9)->minute(46),
                    Carbon::tomorrow()->hour(10)->minute(1),
                ),
            ]),
            WorkBreakFactory::make([
                'id' => 5,
                'timeWindow' => new TimeWindow(
                    Carbon::tomorrow()->hour(9)->minute(0),
                    Carbon::tomorrow()->hour(9)->minute(15),
                ),
            ]),
            AppointmentFactory::make([
                'id' => 4,
                'timeWindow' => new TimeWindow(
                    Carbon::tomorrow()->hour(8)->minute(38),
                    Carbon::tomorrow()->hour(8)->minute(59),
                ),
            ]),
            AppointmentFactory::make([
                'id' => 2,
                'timeWindow' => new TimeWindow(
                    Carbon::tomorrow()->hour(8)->minute(16),
                    Carbon::tomorrow()->hour(8)->minute(34),
                ),
            ]),
            AppointmentFactory::make([
                'id' => 7,
                'timeWindow' => new TimeWindow(
                    Carbon::tomorrow()->hour(9)->minute(21),
                    Carbon::tomorrow()->hour(9)->minute(45),
                ),
            ]),
            TravelFactory::make([
                'id' => 1,
                'timeWindow' => new TimeWindow(
                    Carbon::tomorrow()->hour(8),
                    Carbon::tomorrow()->hour(8)->minute(15),
                ),
            ]),
            TravelFactory::make([
                'id' => 6,
                'timeWindow' => new TimeWindow(
                    Carbon::tomorrow()->hour(9)->minute(16),
                    Carbon::tomorrow()->hour(9)->minute(20),
                ),
            ]),
            TravelFactory::make([
                'id' => 3,
                'timeWindow' => new TimeWindow(
                    Carbon::tomorrow()->hour(8)->minute(35),
                    Carbon::tomorrow()->hour(8)->minute(37),
                ),
            ]),
        ];

        /** @var Route $route */
        $route = RouteFactory::make(['workEvents' => $workEvents]);

        $this->assertEquals(
            [1, 2, 3, 4, 5, 6, 7, 8],
            $route->getWorkEvents()->map(fn (WorkEvent $workEvent) => $workEvent->getId())->all()
        );
        $this->assertEquals(
            [2, 4, 7],
            $route->getAppointments()->map(fn (WorkEvent $workEvent) => $workEvent->getId())->all()
        );
        $this->assertEquals(
            [5, 8],
            $route->getWorkBreaks()->map(fn (WorkEvent $workEvent) => $workEvent->getId())->all()
        );
        $this->assertEquals(
            [1, 3, 6],
            $route->getTravelEvents()->map(fn (WorkEvent $workEvent) => $workEvent->getId())->all()
        );
    }

    /**
     * @test
     *
     * ::setMetric
     */
    public function it_sets_metric(): void
    {
        $metric = TotalWeightedServiceMetricFactory::make();
        $route = $this->buildRouteWithWorkEvents();
        $route->setMetric($metric);

        $this->assertTrue($route->hasMetric(MetricKey::TOTAL_WEIGHTED_SERVICES));
    }

    /**
     * @test
     *
     * ::setGeometry
     * ::getGeometry
     */
    public function it_gets_sets_geometry_correctly(): void
    {
        $geometry = 'geometry_test';
        $route = $this->buildRouteWithWorkEvents();
        $route->setGeometry($geometry);

        $this->assertEquals($geometry, $route->getGeometry());
    }

    /**
     * @test
     *
     * ::getMetric
     */
    public function it_gets_metric(): void
    {
        /** @var Metric $metric */
        $metric = TotalWeightedServiceMetricFactory::make();
        $route = $this->buildRouteWithWorkEvents();
        $route->setMetric($metric);

        $result = $route->getMetric(MetricKey::TOTAL_WEIGHTED_SERVICES);

        $this->assertEquals($metric->getName(), $result->getName());
    }

    /**
     * @test
     *
     * ::getMetrics
     */
    public function it_gets_metrics(): void
    {
        /** @var Metric $metric */
        $metric = TotalWeightedServiceMetricFactory::make();
        $route = $this->buildRouteWithWorkEvents();
        $route->setMetric($metric);

        $result = $route->getMetrics();

        $this->assertTrue($result->isNotEmpty());
        $this->assertEquals(1, $result->count());
        $this->assertEquals($metric, $result->get(MetricKey::TOTAL_WEIGHTED_SERVICES->value));
    }

    /**
     * @test
     *
     * ::getMetrics
     */
    public function it_returns_true_when_route_is_reschedule_route(): void
    {
        /** @var Route $route */
        $route = RouteFactory::make([
            'workEvents' => [],
            'servicePro' => ServiceProFactory::make([
                'name' => '#Reschedule Route#',
            ]),
        ]);

        $this->assertTrue($route->isRescheduleRoute());
    }

    /**
     * @test
     *
     * ::getOptimizationScore
     */
    public function it_returns_optimization_score(): void
    {
        /** @var Metric $metric */
        $metric = TotalWeightedServiceMetricFactory::make([
            'value' => 10,
        ]);
        $route = $this->buildRouteWithWorkEvents();
        $route->setMetric($metric);

        $result = $route->getOptimizationScore();
        $expectedScore = new Score(0.71);
        $this->assertEquals($expectedScore, $result);
    }

    /**
     * @test
     *
     * ::getOptimizationScore
     */
    public function it_returns_zero_as_optimization_score_when_no_metric_added_to_route(): void
    {
        $route = $this->buildRouteWithWorkEvents();

        $result = $route->getOptimizationScore();
        $expectedScore = new Score(0);
        $this->assertEquals($expectedScore, $result);
    }

    /**
     * @test
     */
    public function it_is_cloned_with_new_work_events(): void
    {
        /** @var Route $route */
        $route = RouteFactory::make([
            'workEvents' => [AppointmentFactory::make()],
        ]);

        /** @var Appointment $originalAppointment */
        $originalAppointment = $route->getAppointments()->first();

        /** @var Route $routeClone */
        $routeClone = clone $route;

        /** @var Appointment $clonedAppointment */
        $clonedAppointment = $routeClone->getAppointments()->first();

        $originalAppointmentSplId = spl_object_id($originalAppointment);
        $clonedAppointmentSplId = spl_object_id($clonedAppointment);

        $this->assertNotEquals($originalAppointmentSplId, $clonedAppointmentSplId);
    }

    /**
     * @test
     *
     * ::getCapacity
     */
    public function it_sets_capacity(): void
    {
        $route = $this->buildRouteWithWorkEvents();

        $route->setCapacity(99);

        $this->assertEquals(99, $route->getCapacity());
    }

    /**
     * @test
     *
     * ::getCapacity
     */
    public function it_returns_max_capacity_by_default(): void
    {
        $route = $this->buildRouteWithWorkEvents();

        $this->assertEquals(18, $route->getCapacity());
    }

    /**
     * @test
     *
     * ::setNumberOfInsideSales
     */
    public function it_sets_inside_sales(): void
    {
        $route = $this->buildRouteWithWorkEvents();

        $route->setNumberOfInsideSales(5);

        $this->assertEquals(5, $route->getConfig()->getInsideSales());
    }

    /**
     * @test
     *
     * ::enableRouteSummary
     */
    public function it_enables_route_summary(): void
    {
        $route = $this->buildRouteWithWorkEvents();

        $route->enableRouteSummary();

        $this->assertEquals(1, $route->getConfig()->getSummary());
    }

    private function buildRouteWithWorkEvents(array $workEvents = []): Route
    {
        return RouteFactory::make([
            'workEvents' => $workEvents,
            'servicePro' => $this->servicePro,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->servicePro);
    }
}
