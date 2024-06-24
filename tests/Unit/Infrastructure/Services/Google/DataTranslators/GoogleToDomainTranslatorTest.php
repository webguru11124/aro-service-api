<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Google\DataTranslators;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Entities\WorkEvent\Travel;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use App\Domain\RouteOptimization\Exceptions\WorkEventNotFoundAfterOptimizationException;
use App\Domain\RouteOptimization\Factories\OptimizationStateFactory;
use App\Domain\RouteOptimization\ValueObjects\WorkEvent\Waiting;
use App\Domain\SharedKernel\Entities\Office;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Infrastructure\Services\Google\DataTranslators\GoogleToDomainTranslator;
use Carbon\Carbon;
use Google\Cloud\Optimization\V1\OptimizeToursResponse;
use Google\Cloud\Optimization\V1\Shipment;
use Google\Cloud\Optimization\V1\Shipment\VisitRequest;
use Google\Cloud\Optimization\V1\ShipmentModel;
use Google\Cloud\Optimization\V1\ShipmentRoute;
use Google\Cloud\Optimization\V1\ShipmentRoute\PBBreak;
use Google\Cloud\Optimization\V1\ShipmentRoute\Transition;
use Google\Cloud\Optimization\V1\ShipmentRoute\Visit;
use Google\Cloud\Optimization\V1\SkippedShipment;
use Google\Cloud\Optimization\V1\Vehicle;
use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Timestamp;
use Google\Type\LatLng;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\WorkBreakFactory;
use Tests\Tools\TestValue;
use Tests\Traits\TranslatorHelpers;

class GoogleToDomainTranslatorTest extends TestCase
{
    use TranslatorHelpers;

    private const OPTIMIZATION_ENGINE = OptimizationEngine::GOOGLE;

    private GoogleToDomainTranslator $translator;
    private Office $office;

    private OptimizationStateFactory|MockInterface $mockOptimizationStateFactory;
    private OptimizeToursResponse|MockInterface $mockResponse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->office = OfficeFactory::make();

        $this->mockOptimizationStateFactory = Mockery::mock(OptimizationStateFactory::class);
        $this->mockResponse = Mockery::mock(OptimizeToursResponse::class);

        $this->translator = new GoogleToDomainTranslator($this->mockOptimizationStateFactory);
    }

    /**
     * @test
     *
     * ::translate
     */
    public function it_translates_google_response(): void
    {
        $sourceOptimizationState = $this->buildSourceOptimizationState();
        $this->setMockOptimizationStateFactoryExpectations($sourceOptimizationState);
        $this->setMockResponseExpectations();

        $result = $this->translate($sourceOptimizationState);

        $this->assertEquals(OptimizationEngine::GOOGLE, $result->getOptimizationEngine());
        $this->assertEquals(OptimizationStatus::POST, $result->getStatus());
        $this->assertCount(0, $result->getUnassignedAppointments());
        $this->assertCount(0, $result->getRoutes());
    }

    /**
     * @test
     *
     * ::translate
     */
    public function it_translates_unassigned_appointments(): void
    {
        $sourceOptimizationState = $this->buildSourceOptimizationState([
            $this->buildRoute([
                AppointmentFactory::make(['id' => TestValue::APPOINTMENT_ID]),
                AppointmentFactory::make(['id' => TestValue::APPOINTMENT_ID + 1]),
            ]),
        ]);
        $this->setMockOptimizationStateFactoryExpectations($sourceOptimizationState);

        $this->setMockResponseExpectations(
            skippedShipments: [
                [],
                ['index' => 1],
            ],
        );

        $result = $this->translate($sourceOptimizationState);

        $this->assertCount(2, $result->getUnassignedAppointments());
        $this->assertEquals(TestValue::APPOINTMENT_ID, $result->getUnassignedAppointments()->first()->getId());
        $this->assertEquals(TestValue::APPOINTMENT_ID + 1, $result->getUnassignedAppointments()->last()->getId());
    }

    /**
     * @test
     *
     * ::translate
     */
    public function it_translates_start_location(): void
    {
        $sourceRoute = $this->buildRoute();
        $sourceOptimizationState = $this->buildSourceOptimizationState([$sourceRoute]);
        $this->setMockOptimizationStateFactoryExpectations($sourceOptimizationState);

        $arrivalAt = TestValue::ROUTE_START_TIME;
        $this->setMockResponseExpectations(
            routes: [
                [
                    'vehicle_label' => TestValue::ROUTE_ID,
                    'vehicle_start_time' => $this->buildTimestamp($arrivalAt),
                    'vehicle_end_time' => $this->buildTimestamp($arrivalAt),
                    'visits' => [],
                ],
            ],
        );

        $result = $this->translate($sourceOptimizationState);

        /** @var Route $route */
        $route = $result->getRoutes()->first();
        $this->assertEquals($arrivalAt, $route->getStartLocation()->getTimeWindow()->getStartAt()->toTimeString());
        $this->assertEquals($sourceRoute->getStartLocation()->getLocation(), $route->getStartLocation()->getLocation());
    }

    /**
     * @test
     *
     * ::translate
     */
    public function it_translates_end_location(): void
    {
        $sourceRoute = $this->buildRoute();
        $sourceOptimizationState = $this->buildSourceOptimizationState([$sourceRoute]);
        $this->setMockOptimizationStateFactoryExpectations($sourceOptimizationState);

        $arrivalAt = TestValue::ROUTE_END_TIME;
        $this->setMockResponseExpectations(
            routes: [
                [
                    'vehicle_label' => TestValue::ROUTE_ID,
                    'vehicle_start_time' => $this->buildTimestamp($arrivalAt),
                    'vehicle_end_time' => $this->buildTimestamp($arrivalAt),
                    'visits' => [],
                ],
            ],
        );

        $result = $this->translate($sourceOptimizationState);

        /** @var Route $route */
        $route = $result->getRoutes()->first();
        $this->assertEquals($arrivalAt, $route->getEndLocation()->getTimeWindow()->getStartAt()->toTimeString());
        $this->assertEquals($sourceRoute->getEndLocation()->getLocation(), $route->getEndLocation()->getLocation());
    }

    /**
     * @test
     *
     * ::translate
     */
    public function it_throws_exception_when_work_event_not_found_after_optimization(): void
    {
        $sourceOptimizationState = $this->buildSourceOptimizationState([$this->buildRoute()]);
        $this->setMockOptimizationStateFactoryExpectations($sourceOptimizationState);

        $this->setMockResponseExpectations(
            routes: [
                [
                    'vehicle_label' => TestValue::ROUTE_ID,
                    'vehicle_start_time' => $this->buildTimestamp(TestValue::ROUTE_START_TIME),
                    'vehicle_end_time' => $this->buildTimestamp(TestValue::ROUTE_END_TIME),
                    'visits' => $this->buildVisits([
                        [
                            'shipment_index' => 5,
                            'start_time' => $this->buildTimestamp('2023-12-08T10:11:21Z'),
                            'detour' => $this->buildDuration(0),
                            'visit_label' => '157841',
                        ],
                    ]),
                ],
            ]
        );

        $this->expectException(WorkEventNotFoundAfterOptimizationException::class);
        $this->translate($sourceOptimizationState);
    }

    /**
     * @test
     *
     * ::translate
     */
    public function it_does_not_add_skipped_routes(): void
    {
        $sourceOptimizationState = $this->buildSourceOptimizationState([$this->buildRoute()]);
        $this->setMockOptimizationStateFactoryExpectations($sourceOptimizationState);

        $this->setMockResponseExpectations(
            routes: [
                [], // empty route should be skipped
            ]
        );

        $result = $this->translate($sourceOptimizationState);

        $this->assertTrue($result->getRoutes()->isEmpty());
    }

    /**
     * @test
     *
     * ::translate
     */
    public function it_translates_appointment(): void
    {
        $sourceOptimizationState = $this->buildSourceOptimizationState([
            $this->buildRoute([
                AppointmentFactory::make([
                    'id' => TestValue::APPOINTMENT_ID,
                    'duration' => Duration::fromMinutes(21),
                    'setupDuration' => Duration::fromMinutes(4),
                ]),
            ]),
        ]);
        $this->setMockOptimizationStateFactoryExpectations($sourceOptimizationState);

        $arrivalAt = '08:13:00';
        $this->setMockResponseExpectations(
            routes: [
                [
                    'vehicle_label' => TestValue::ROUTE_ID,
                    'vehicle_start_time' => $this->buildTimestamp(TestValue::ROUTE_START_TIME),
                    'vehicle_end_time' => $this->buildTimestamp(TestValue::ROUTE_END_TIME),
                    'visits' => $this->buildVisits([
                        [
                            'start_time' => $this->buildTimestamp($arrivalAt),
                            'visit_label' => TestValue::APPOINTMENT_ID,
                        ],
                    ]),
                ],
            ]
        );

        $result = $this->translate($sourceOptimizationState);

        /** @var Route $route */
        $route = $result->getRoutes()->first();
        /** @var Appointment $appointment */
        $appointment = $route->getAppointments()->first();
        $this->assertEquals($arrivalAt, $appointment->getTimeWindow()->getStartAt()->toTimeString());
        $this->assertEquals('08:38:00', $appointment->getTimeWindow()->getEndAt()->toTimeString());
        $this->assertEquals(21, $appointment->getDuration()->getTotalMinutes());
        $this->assertEquals(4, $appointment->getSetupDuration()->getTotalMinutes());
    }

    /**
     * @test
     *
     * ::translate
     */
    public function it_translates_break(): void
    {
        $sourceOptimizationState = $this->buildSourceOptimizationState([
            $this->buildRoute([
                WorkBreakFactory::make([
                    'id' => TestValue::WORK_BREAK_ID,
                ]),
            ]),
        ]);
        $this->setMockOptimizationStateFactoryExpectations($sourceOptimizationState);

        $arrivalAt = '09:30:00';
        $this->setMockResponseExpectations(
            routes: [
                [
                    'vehicle_label' => TestValue::ROUTE_ID,
                    'vehicle_start_time' => $this->buildTimestamp(TestValue::ROUTE_START_TIME),
                    'vehicle_end_time' => $this->buildTimestamp(TestValue::ROUTE_END_TIME),
                    'breaks' => $this->buildBreaks([
                        [
                            'start_time' => $this->buildTimestamp($arrivalAt),
                            'duration' => $this->buildDuration(TestValue::WORK_BREAK_DURATION * 60),
                        ],
                    ]),
                ],
            ]
        );

        $result = $this->translate($sourceOptimizationState);

        /** @var Route $route */
        $route = $result->getRoutes()->first();
        /** @var WorkBreak $workBreak */
        $workBreak = $route->getWorkBreaks()->first();
        $this->assertEquals($arrivalAt, $workBreak->getTimeWindow()->getStartAt()->toTimeString());
        $this->assertEquals('09:45:00', $workBreak->getTimeWindow()->getEndAt()->toTimeString());
        $this->assertEquals(TestValue::WORK_BREAK_DURATION, $workBreak->getDuration()->getTotalMinutes());
    }

    /**
     * @test
     *
     * ::translate
     */
    public function it_translates_travels(): void
    {
        $sourceOptimizationState = $this->buildSourceOptimizationState([$this->buildRoute()]);
        $this->setMockOptimizationStateFactoryExpectations($sourceOptimizationState);

        $travelDuration = 10 * 60; // 10 min
        $travelDistance = 12300;
        $startAt = '08:40:00';
        $endAt = '08:50:00';
        $this->setMockResponseExpectations(
            routes: [
                [
                    'vehicle_label' => TestValue::ROUTE_ID,
                    'vehicle_start_time' => $this->buildTimestamp(TestValue::ROUTE_START_TIME),
                    'vehicle_end_time' => $this->buildTimestamp(TestValue::ROUTE_END_TIME),
                    'transitions' => $this->buildTransitions([
                        [
                            'start_time' => $this->buildTimestamp($startAt),
                            'travel_duration' => $this->buildDuration($travelDuration),
                            'travel_distance_meters' => $travelDistance,
                        ],
                    ]),
                ],
            ]
        );

        $result = $this->translate($sourceOptimizationState);

        /** @var Route $route */
        $route = $result->getRoutes()->first();
        /** @var Travel $travel */
        $travel = $route->getTravelEvents()->first();
        $this->assertEquals($startAt, $travel->getTimeWindow()->getStartAt()->toTimeString());
        $this->assertEquals($endAt, $travel->getTimeWindow()->getEndAt()->toTimeString());
        $this->assertEquals($travelDuration, $travel->getDuration()->getTotalSeconds());
        $this->assertEquals($travelDistance, $travel->getDistance()->getIntMeters());
    }

    /**
     * @test
     *
     * ::translate
     */
    public function it_translates_travels_with_break_and_waiting(): void
    {
        $sourceOptimizationState = $this->buildSourceOptimizationState([$this->buildRoute()]);
        $this->setMockOptimizationStateFactoryExpectations($sourceOptimizationState);

        $travelDuration = 1200; // 20 min
        $travelDistance = 25000;
        $startAt = '09:35:00';
        $breakDuration = 900;
        $waitDuration = 250;
        $this->setMockResponseExpectations(
            routes: [
                [
                    'vehicle_label' => TestValue::ROUTE_ID,
                    'vehicle_start_time' => $this->buildTimestamp(TestValue::ROUTE_START_TIME),
                    'vehicle_end_time' => $this->buildTimestamp(TestValue::ROUTE_END_TIME),
                    'transitions' => $this->buildTransitions([
                        [
                            'start_time' => $this->buildTimestamp($startAt),
                            'wait_duration' => $this->buildDuration($waitDuration),
                            'break_duration' => $this->buildDuration($breakDuration),
                            'travel_duration' => $this->buildDuration($travelDuration),
                            'travel_distance_meters' => $travelDistance,
                        ],
                    ]),
                ],
            ]
        );

        $result = $this->translate($sourceOptimizationState);

        /** @var Route $route */
        $route = $result->getRoutes()->first();
        /** @var Travel $travel */
        $travel = $route->getTravelEvents()->first();
        $this->assertEquals('09:54:10', $travel->getTimeWindow()->getStartAt()->toTimeString());
        $this->assertEquals('10:14:10', $travel->getTimeWindow()->getEndAt()->toTimeString());
        $this->assertEquals($travelDuration, $travel->getDuration()->getTotalSeconds());

        /** @var Waiting $waiting */
        $waiting = $route->getWaitingEvents()->first();
        $this->assertEquals('09:35:00', $waiting->getTimeWindow()->getStartAt()->toTimeString());
        $this->assertEquals('09:39:10', $waiting->getTimeWindow()->getEndAt()->toTimeString());
        $this->assertEquals($waitDuration, $waiting->getDuration()->getTotalSeconds());
    }

    /**
     * @test
     *
     * ::translateSingleRoute
     */
    public function it_does_not_translate_response_when_there_are_no_routes(): void
    {
        /** @var Route $route */
        $route = RouteFactory::make([
            'date' => Carbon::tomorrow(),
        ]);

        $this->mockResponse
            ->shouldReceive('getRoutes')
            ->once()
            ->andReturn($this->buildRoutes([]));

        $resultRoute = $this->translator->translateSingleRoute(
            $this->mockResponse,
            $route
        );

        $this->assertEquals($route->getId(), $resultRoute->getId());
    }

    /**
     * @test
     *
     * ::translateSingleRoute
     */
    public function it_translates_single_route_from_google_response(): void
    {
        /** @var Route $route */
        $route = RouteFactory::make([
            'date' => Carbon::tomorrow(),
        ]);

        $this->mockResponse
            ->shouldReceive('getRoutes')
            ->twice()
            ->andReturn($this->buildRoutes([
                [
                    'vehicle_label' => TestValue::ROUTE_ID,
                    'vehicle_start_time' => $this->buildTimestamp(TestValue::ROUTE_START_TIME),
                    'vehicle_end_time' => $this->buildTimestamp(TestValue::ROUTE_END_TIME),
                    'visits' => [],
                ],
            ]));

        $resultRoute = $this->translator->translateSingleRoute(
            $this->mockResponse,
            $route
        );

        $this->assertEquals(TestValue::ROUTE_ID, $resultRoute->getId());
    }

    /**
     * @test
     *
     * ::translateSingleRoute
     */
    public function it_translates_single_route_from_google_response_with_appointments(): void
    {
        /** @var Route $route */
        $route = RouteFactory::make([
            'date' => Carbon::tomorrow($this->office->getTimeZone()),
            'workEvents' => [
                AppointmentFactory::make([
                    'id' => TestValue::APPOINTMENT_ID,
                    'duration' => Duration::fromMinutes(21),
                    'setupDuration' => Duration::fromMinutes(4),
                ]),
            ],
        ]);

        $arrivalAt = '10:50:00';
        $this->mockResponse
            ->shouldReceive('getRoutes')
            ->twice()
            ->andReturn($this->buildRoutes([
                [
                    'vehicle_label' => TestValue::ROUTE_ID,
                    'vehicle_start_time' => $this->buildTimestamp(TestValue::ROUTE_START_TIME),
                    'vehicle_end_time' => $this->buildTimestamp(TestValue::ROUTE_END_TIME),
                    'visits' => $this->buildVisits([
                        [
                            'start_time' => $this->buildTimestamp($arrivalAt),
                            'visit_label' => TestValue::APPOINTMENT_ID,
                        ],
                    ]),
                ],
            ]));

        $resultRoute = $this->translator->translateSingleRoute(
            $this->mockResponse,
            $route
        );

        /** @var Appointment $appointment */
        $appointment = $resultRoute->getAppointments()->first();
        $this->assertEquals($arrivalAt, $appointment->getTimeWindow()->getStartAt()->toTimeString());
        $this->assertEquals('11:15:00', $appointment->getTimeWindow()->getEndAt()->toTimeString());
    }

    private function buildTimestamp(string $time): Timestamp
    {
        return new Timestamp([
            'seconds' => Carbon::today($this->office->getTimeZone())->setTimeFromTimeString($time)->timestamp,
        ]);
    }

    private function buildDuration(int $seconds): \Google\Protobuf\Duration
    {
        return new \Google\Protobuf\Duration([
            'seconds' => $seconds,
        ]);
    }

    private function buildVisits(array $visits): RepeatedField
    {
        $result = new RepeatedField(GPBType::MESSAGE, Visit::class);

        foreach ($visits as $visit) {
            $result[] = new Visit($visit);
        }

        return $result;
    }

    private function buildRoutes(array $routes): RepeatedField
    {
        $result = new RepeatedField(GPBType::MESSAGE, ShipmentRoute::class);

        foreach ($routes as $route) {
            $result[] = new ShipmentRoute($route);
        }

        return $result;
    }

    private function buildBreaks(array $breaks): RepeatedField
    {
        $result = new RepeatedField(GPBType::MESSAGE, PBBreak::class);

        foreach ($breaks as $break) {
            $result[] = new PBBreak($break);
        }

        return $result;
    }

    private function buildTransitions(array $transitions): RepeatedField
    {
        $result = new RepeatedField(GPBType::MESSAGE, Transition::class);

        foreach ($transitions as $transition) {
            $result[] = new Transition($transition);
        }

        return $result;
    }

    private function buildSkippedShipments(array $skippedShipments): RepeatedField
    {
        $result = new RepeatedField(GPBType::MESSAGE, SkippedShipment::class);

        foreach ($skippedShipments as $skippedShipment) {
            $result[] = new SkippedShipment($skippedShipment);
        }

        return $result;
    }

    private function setMockResponseExpectations(array $routes = [], array $skippedShipments = []): void
    {
        $this->mockResponse->shouldReceive('getRoutes')
            ->andReturn($this->buildRoutes($routes));

        $this->mockResponse->shouldReceive('getSkippedShipments')
            ->andReturn($this->buildSkippedShipments($skippedShipments));
    }

    private function getShipments(OptimizationState $state): RepeatedField
    {
        $shipments = new RepeatedField(GPBType::MESSAGE, Shipment::class);

        foreach ($state->getAllAppointments() as $appointment) {
            $visitRequest = (new VisitRequest())
                ->setLabel((string) $appointment->getId());
            $shipments[] = (new Shipment())
                ->setDeliveries([$visitRequest]);
        }

        return $shipments;
    }

    private function getVehicles(OptimizationState $state): RepeatedField
    {
        $vehicles = new RepeatedField(GPBType::MESSAGE, Vehicle::class);

        foreach ($state->getRoutes() as $route) {
            $startCoordinate = $route->getStartLocation()->getLocation();
            $startLocation = (new LatLng())
                ->setLatitude($startCoordinate->getLatitude())
                ->setLongitude($startCoordinate->getLongitude());
            $endCoordinate = $route->getEndLocation()->getLocation();
            $endLocation = (new LatLng())
                ->setLatitude($endCoordinate->getLatitude())
                ->setLongitude($endCoordinate->getLongitude());
            $vehicles[] = (new Vehicle())
                ->setLabel((string) $route->getId())
                ->setStartLocation($startLocation)
                ->setEndLocation($endLocation);
        }

        return $vehicles;
    }

    private function buildShipmentModel(OptimizationState $state): ShipmentModel
    {
        $shipments = $this->getShipments($state);
        $vehicles = $this->getVehicles($state);

        return (new ShipmentModel())
            ->setShipments($shipments)
            ->setVehicles($vehicles);
    }

    private function translate(OptimizationState $sourceOptimizationState): OptimizationState
    {
        return $this->translator->translate(
            $this->buildShipmentModel($sourceOptimizationState),
            $this->mockResponse,
            $sourceOptimizationState,
            OptimizationStatus::POST
        );
    }
}
