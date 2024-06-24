<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Vroom\DataTranslators;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Entities\WorkEvent\Lunch;
use App\Domain\RouteOptimization\Entities\WorkEvent\Meeting;
use App\Domain\RouteOptimization\Entities\WorkEvent\ReservedTime;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use App\Domain\RouteOptimization\Exceptions\WorkEventNotFoundAfterOptimizationException;
use App\Domain\RouteOptimization\Factories\OptimizationStateFactory;
use App\Domain\RouteOptimization\ValueObjects\WorkEvent\Waiting;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Services\Vroom\DataTranslators\VroomToDomainTranslator;
use App\Infrastructure\Services\Vroom\Enums\StepType;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\LunchFactory;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\Factories\ReservedTimeFactory;
use Tests\Tools\Factories\RouteOptimization\MeetingFactory;
use Tests\Tools\Factories\WorkBreakFactory;
use Tests\Tools\TestValue;
use Tests\Traits\TranslatorHelpers;

class VroomToDomainTranslatorTest extends TestCase
{
    use TranslatorHelpers;

    private const OPTIMIZATION_ENGINE = OptimizationEngine::VROOM;

    private Office $office;
    private VroomToDomainTranslator $translator;
    private OptimizationStateFactory|MockInterface $mockOptimizationStateFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->office = OfficeFactory::make();
        $this->mockOptimizationStateFactory = Mockery::mock(OptimizationStateFactory::class);
        $this->translator = new VroomToDomainTranslator($this->mockOptimizationStateFactory);
    }

    /**
     * @test
     *
     * ::translate
     */
    public function it_translates_vroom_response(): void
    {
        $sourceOptimizationState = $this->buildSourceOptimizationState();
        $this->setMockOptimizationStateFactoryExpectations($sourceOptimizationState);

        $result = $this->translate($this->buildVroomResponse(), $sourceOptimizationState);

        $this->assertEquals(OptimizationEngine::VROOM, $result->getOptimizationEngine());
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
                AppointmentFactory::make(['id' => 29111145]),
                AppointmentFactory::make(['id' => 29113515]),
            ]),
        ]);
        $this->setMockOptimizationStateFactoryExpectations($sourceOptimizationState);

        $vroomResponse = $this->buildVroomResponse(
            unassigned: [
                ['id' => 29111145, 'type' => StepType::JOB->value],
                ['id' => 29113515, 'type' => StepType::JOB->value],
            ]
        );

        $result = $this->translate($vroomResponse, $sourceOptimizationState);

        $this->assertCount(2, $result->getUnassignedAppointments());
        $this->assertEquals(29111145, $result->getUnassignedAppointments()->first()->getId());
        $this->assertEquals(29113515, $result->getUnassignedAppointments()->last()->getId());
    }

    /**
     * @test
     *
     * ::translate
     */
    public function it_translates_start_location(): void
    {
        $sourceOptimizationState = $this->buildSourceOptimizationState([$this->buildRoute()]);
        $this->setMockOptimizationStateFactoryExpectations($sourceOptimizationState);

        $arrivalAt = '08:15:00';
        $vroomResponse = $this->buildVroomResponse(
            routes: [
                [
                    'vehicle' => TestValue::ROUTE_ID,
                    'steps' => [
                        [
                            'type' => StepType::START->value,
                            'setup' => 0,
                            'service' => 0,
                            'waiting_time' => 0,
                            'arrival' => $this->buildArrivalTimestamp($arrivalAt),
                            'location' => [TestValue::MIN_LONGITUDE, TestValue::MIN_LATITUDE],
                        ],
                    ],
                ],
            ]
        );

        $result = $this->translate($vroomResponse, $sourceOptimizationState);

        /** @var Route $route */
        $route = $result->getRoutes()->first();
        $this->assertEquals($arrivalAt, $route->getTimeWindow()->getStartAt()->toTimeString());
        $this->assertEquals(TestValue::MIN_LATITUDE, $route->getStartLocation()->getLocation()->getLatitude());
        $this->assertEquals(TestValue::MIN_LONGITUDE, $route->getStartLocation()->getLocation()->getLongitude());
    }

    /**
     * @test
     *
     * ::translate
     */
    public function it_translates_end_location(): void
    {
        $sourceOptimizationState = $this->buildSourceOptimizationState([$this->buildRoute()]);
        $this->setMockOptimizationStateFactoryExpectations($sourceOptimizationState);

        $arrivalAt = '17:45:00';
        $vroomResponse = $this->buildVroomResponse(
            routes: [
                [
                    'vehicle' => TestValue::ROUTE_ID,
                    'steps' => [
                        [
                            'type' => StepType::END->value,
                            'setup' => 0,
                            'service' => 0,
                            'waiting_time' => 0,
                            'arrival' => $this->buildArrivalTimestamp($arrivalAt),
                            'location' => [TestValue::MIN_LONGITUDE, TestValue::MIN_LATITUDE],
                        ],
                    ],
                ],
            ]
        );

        $result = $this->translate($vroomResponse, $sourceOptimizationState);

        /** @var Route $route */
        $route = $result->getRoutes()->first();
        $this->assertEquals($arrivalAt, $route->getTimeWindow()->getEndAt()->toTimeString());
        $this->assertEquals(TestValue::MIN_LATITUDE, $route->getEndLocation()->getLocation()->getLatitude());
        $this->assertEquals(TestValue::MIN_LONGITUDE, $route->getEndLocation()->getLocation()->getLongitude());
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

        $vroomResponse = $this->buildVroomResponse(
            routes: [
                [
                    'vehicle' => TestValue::ROUTE_ID,
                    'steps' => [
                        [
                            'type' => StepType::BREAK->value,
                            'id' => 1,
                            'setup' => 0,
                            'service' => 15 * 60,
                            'description' => '15 Min Break',
                            'waiting_time' => 0,
                            'arrival' => $this->buildArrivalTimestamp('09:30:00'),
                        ],
                    ],
                ],
            ]
        );

        $this->expectException(WorkEventNotFoundAfterOptimizationException::class);
        $this->translate($vroomResponse, $sourceOptimizationState);
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
                WorkBreakFactory::make(['id' => 1]),
            ]),
        ]);
        $this->setMockOptimizationStateFactoryExpectations($sourceOptimizationState);

        $vroomResponse = $this->buildVroomResponse(
            routes: [
                [
                    'vehicle' => TestValue::ROUTE_ID,
                    'steps' => [
                        [
                            'type' => StepType::BREAK->value,
                            'id' => 1,
                            'setup' => 0,
                            'service' => 15 * 60,
                            'description' => '15 Min Break',
                            'waiting_time' => 0,
                            'arrival' => $this->buildArrivalTimestamp('09:30:00'),
                        ],
                    ],
                ],
            ]
        );

        $result = $this->translate($vroomResponse, $sourceOptimizationState);

        /** @var Route $route */
        $route = $result->getRoutes()->first();
        /** @var WorkBreak $workBreak */
        $workBreak = $route->getWorkBreaks()->first();
        $this->assertEquals('09:30:00', $workBreak->getTimeWindow()->getStartAt()->toTimeString());
        $this->assertEquals('09:45:00', $workBreak->getTimeWindow()->getEndAt()->toTimeString());
    }

    /**
     * @test
     *
     * ::translate
     */
    public function it_translates_lunch(): void
    {
        $sourceOptimizationState = $this->buildSourceOptimizationState([
            $this->buildRoute([
                LunchFactory::make(['id' => 2]),
            ]),
        ]);
        $this->setMockOptimizationStateFactoryExpectations($sourceOptimizationState);

        $vroomResponse = $this->buildVroomResponse(
            routes: [
                [
                    'vehicle' => TestValue::ROUTE_ID,
                    'steps' => [
                        [
                            'type' => StepType::BREAK->value,
                            'id' => 2,
                            'setup' => 0,
                            'service' => 30 * 60,
                            'description' => 'Lunch',
                            'waiting_time' => 0,
                            'arrival' => $this->buildArrivalTimestamp('12:45:00'),
                        ],
                    ],
                ],
            ]
        );

        $result = $this->translate($vroomResponse, $sourceOptimizationState);

        /** @var Route $route */
        $route = $result->getRoutes()->first();
        /** @var Lunch $lunch */
        $lunch = $route->getWorkBreaks()->first();
        $this->assertEquals('12:45:00', $lunch->getTimeWindow()->getStartAt()->toTimeString());
        $this->assertEquals('13:15:00', $lunch->getTimeWindow()->getEndAt()->toTimeString());
    }

    /**
     * @test
     *
     * ::translate
     */
    public function it_translates_reserved_time(): void
    {
        $sourceOptimizationState = $this->buildSourceOptimizationState([
            $this->buildRoute([
                ReservedTimeFactory::make(['id' => 3]),
            ]),
        ]);
        $this->setMockOptimizationStateFactoryExpectations($sourceOptimizationState);

        $vroomResponse = $this->buildVroomResponse(
            routes: [
                [
                    'vehicle' => TestValue::ROUTE_ID,
                    'steps' => [
                        [
                            'type' => StepType::BREAK->value,
                            'id' => 3,
                            'setup' => 0,
                            'service' => 3600,
                            'description' => 'Not working',
                            'waiting_time' => 0,
                            'arrival' => $this->buildArrivalTimestamp('16:00:00'),
                        ],
                    ],
                ],
            ]
        );

        $result = $this->translate($vroomResponse, $sourceOptimizationState);

        /** @var Route $route */
        $route = $result->getRoutes()->first();
        /** @var ReservedTime $reservedTime */
        $reservedTime = $route->getReservedTimes()->first();
        $this->assertEquals('16:00:00', $reservedTime->getTimeWindow()->getStartAt()->toTimeString());
        $this->assertEquals('17:00:00', $reservedTime->getTimeWindow()->getEndAt()->toTimeString());
    }

    /**
     * @test
     *
     * ::translate
     */
    public function it_adds_waiting_before_route_start(): void
    {
        $sourceOptimizationState = $this->buildSourceOptimizationState([$this->buildRoute()]);
        $this->setMockOptimizationStateFactoryExpectations($sourceOptimizationState);

        $vroomResponse = $this->buildVroomResponse(
            routes: [
                [
                    'vehicle' => TestValue::ROUTE_ID,
                    'steps' => [
                        [
                            'type' => StepType::START->value,
                            'setup' => 0,
                            'service' => 0,
                            'waiting_time' => 0,
                            'arrival' => $this->buildArrivalTimestamp('08:30:00'),
                            'location' => [-121.633, 39.1215],
                        ],
                    ],
                ],
            ]
        );

        $result = $this->translate($vroomResponse, $sourceOptimizationState);

        /** @var Route $route */
        $route = $result->getRoutes()->first();
        /** @var Waiting $waiting */
        $waiting = $route->getWaitingEvents()->first();
        $this->assertEquals('08:00:00', $waiting->getTimeWindow()->getStartAt()->toTimeString());
        $this->assertEquals('08:30:00', $waiting->getTimeWindow()->getEndAt()->toTimeString());
    }

    /**
     * @test
     *
     * ::translate
     */
    public function it_adds_waiting_on_route(): void
    {
        $sourceOptimizationState = $this->buildSourceOptimizationState([
            $this->buildRoute([
                WorkBreakFactory::make([
                    'id' => 1,
                ]),
            ]),
        ]);
        $this->setMockOptimizationStateFactoryExpectations($sourceOptimizationState);

        $vroomResponse = $this->buildVroomResponse(
            routes: [
                [
                    'vehicle' => TestValue::ROUTE_ID,
                    'steps' => [
                        [
                            'type' => StepType::START->value,
                            'setup' => 0,
                            'service' => 0,
                            'waiting_time' => 0,
                            'duration' => 0,
                            'distance' => 0,
                            'arrival' => $this->buildArrivalTimestamp('08:00:00'),
                            'location' => [-121.633, 39.1215],
                        ],
                        [
                            'type' => StepType::BREAK->value,
                            'id' => 1,
                            'setup' => 0,
                            'service' => 900,
                            'waiting_time' => 300, // 5 min
                            'duration' => 600,
                            'distance' => 1000,
                            'arrival' => $this->buildArrivalTimestamp('08:10:00'),
                        ],
                    ],
                ],
            ]
        );

        $result = $this->translate($vroomResponse, $sourceOptimizationState);

        /** @var Route $route */
        $route = $result->getRoutes()->first();
        /** @var Waiting $waiting */
        $waiting = $route->getWaitingEvents()->first();
        $this->assertEquals('08:25:00', $waiting->getTimeWindow()->getStartAt()->toTimeString());
        $this->assertEquals('08:30:00', $waiting->getTimeWindow()->getEndAt()->toTimeString());
    }

    /**
     * @test
     *
     * ::translate
     */
    public function it_translates_route_geometry(): void
    {
        $testGeometry = 'q~|~H~|~@';

        $sourceOptimizationState = $this->buildSourceOptimizationState([
            $this->buildRoute([
                AppointmentFactory::make([
                    'id' => TestValue::APPOINTMENT_ID,
                ]),
            ]),
        ]);
        $this->setMockOptimizationStateFactoryExpectations($sourceOptimizationState);

        $vroomResponse = $this->buildVroomResponse(
            routes: [
                [
                    'geometry' => $testGeometry,
                    'vehicle' => TestValue::ROUTE_ID,
                    'steps' => [
                        [
                            'type' => StepType::START->value,
                            'setup' => 0,
                            'service' => 0,
                            'waiting_time' => 0,
                            'duration' => 0,
                            'distance' => 0,
                            'arrival' => $this->buildArrivalTimestamp('08:00:00'),
                            'location' => [-121.633, 39.1215],
                        ],
                        [
                            'type' => StepType::JOB->value,
                            'id' => TestValue::APPOINTMENT_ID,
                            'setup' => 4 * 60, // 4 min
                            'service' => 21 * 60, // 21 min
                            'waiting_time' => 0,
                            'duration' => 600,
                            'distance' => 1000,
                            'arrival' => $this->buildArrivalTimestamp('08:10:00'),
                        ],
                    ],
                ],
            ]
        );

        $result = $this->translate($vroomResponse, $sourceOptimizationState);

        /** @var Route $route */
        $route = $result->getRoutes()->first();

        $this->assertEquals($testGeometry, $route->getGeometry());
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
                ]),
            ]),
        ]);
        $this->setMockOptimizationStateFactoryExpectations($sourceOptimizationState);

        $vroomResponse = $this->buildVroomResponse(
            routes: [
                [
                    'vehicle' => TestValue::ROUTE_ID,
                    'steps' => [
                        [
                            'type' => StepType::START->value,
                            'setup' => 0,
                            'service' => 0,
                            'waiting_time' => 0,
                            'duration' => 0,
                            'distance' => 0,
                            'arrival' => $this->buildArrivalTimestamp('08:00:00'),
                            'location' => [-121.633, 39.1215],
                        ],
                        [
                            'type' => StepType::JOB->value,
                            'id' => TestValue::APPOINTMENT_ID,
                            'setup' => 3 * 60, // 4 min
                            'service' => 21 * 60, // 21 min
                            'waiting_time' => 0,
                            'duration' => 600,
                            'distance' => 1000,
                            'arrival' => $this->buildArrivalTimestamp('08:10:00'),
                        ],
                    ],
                ],
            ]
        );

        $result = $this->translate($vroomResponse, $sourceOptimizationState);

        /** @var Route $route */
        $route = $result->getRoutes()->first();
        /** @var Appointment $appointment */
        $appointment = $route->getAppointments()->first();
        $this->assertEquals('08:10:00', $appointment->getTimeWindow()->getStartAt()->toTimeString());
        $this->assertEquals('08:34:00', $appointment->getTimeWindow()->getEndAt()->toTimeString());
        $this->assertEquals(21, $appointment->getDuration()->getTotalMinutes());
        $this->assertEquals(3, $appointment->getSetupDuration()->getTotalMinutes());
    }

    /**
     * @test
     *
     * ::translate
     */
    public function it_translates_meeting(): void
    {
        $sourceOptimizationState = $this->buildSourceOptimizationState([
            $this->buildRoute([
                MeetingFactory::make([
                    'id' => TestValue::EVENT_ID,
                ]),
            ]),
        ]);
        $this->setMockOptimizationStateFactoryExpectations($sourceOptimizationState);

        $vroomResponse = $this->buildVroomResponse(
            routes: [
                [
                    'vehicle' => TestValue::ROUTE_ID,
                    'steps' => [
                        [
                            'type' => StepType::START->value,
                            'setup' => 0,
                            'service' => 0,
                            'waiting_time' => 0,
                            'duration' => 0,
                            'distance' => 0,
                            'arrival' => $this->buildArrivalTimestamp('08:00:00'),
                            'location' => [-121.633, 39.1215],
                        ],
                        [
                            'type' => StepType::JOB->value,
                            'id' => TestValue::EVENT_ID,
                            'setup' => 0,
                            'service' => 3600,
                            'waiting_time' => 0,
                            'duration' => 600,
                            'distance' => 1000,
                            'arrival' => $this->buildArrivalTimestamp('08:10:00'),
                        ],
                    ],
                ],
            ]
        );

        $result = $this->translate($vroomResponse, $sourceOptimizationState);

        /** @var Route $route */
        $route = $result->getRoutes()->first();
        /** @var Meeting $meeting */
        $meeting = $route->getMeetings()->first();
        $this->assertEquals('08:00:00', $meeting->getExpectedArrival()->getStartAt()->toTimeString());
        $this->assertEquals('09:00:00', $meeting->getExpectedArrival()->getEndAt()->toTimeString());
        $this->assertEquals(60, $meeting->getDuration()->getTotalMinutes());
    }

    /**
     * @test
     *
     * ::translateSingleRoute
     */
    public function it_translates_single_route(): void
    {
        $route = $this->buildRoute([
            AppointmentFactory::make([
                'id' => TestValue::APPOINTMENT_ID,
            ]),
        ]);

        $vroomResponse = $this->buildVroomResponse(
            routes: [
                [
                    'vehicle' => TestValue::ROUTE_ID,
                    'steps' => [
                        [
                            'type' => StepType::START->value,
                            'setup' => 0,
                            'service' => 0,
                            'waiting_time' => 0,
                            'duration' => 0,
                            'distance' => 0,
                            'arrival' => $this->buildArrivalTimestamp('08:00:00'),
                            'location' => [-121.633, 39.1215],
                        ],
                        [
                            'type' => StepType::JOB->value,
                            'id' => TestValue::APPOINTMENT_ID,
                            'setup' => 3 * 60,
                            'service' => 20 * 60,
                            'waiting_time' => 0,
                            'duration' => 600,
                            'distance' => 1000,
                            'arrival' => $this->buildArrivalTimestamp('08:10:00'),
                        ],
                    ],
                ],
            ]
        );

        /** @var Route $result */
        $result = $this->translator->translateSingleRoute($vroomResponse, $route);

        /** @var Appointment $appointment */
        $appointment = $result->getAppointments()->first();
        $this->assertEquals('08:10:00', $appointment->getTimeWindow()->getStartAt()->toTimeString());
        $this->assertEquals('08:33:00', $appointment->getTimeWindow()->getEndAt()->toTimeString());
    }

    /**
     * @test
     *
     * ::translateSingleRoute
     */
    public function it_returns_empty_route_when_response_has_no_routes(): void
    {
        $route = $this->buildRoute([
            AppointmentFactory::make([
                'id' => TestValue::APPOINTMENT_ID,
            ]),
        ]);

        $vroomResponse = $this->buildVroomResponse();

        /** @var Route $result */
        $result = $this->translator->translateSingleRoute($vroomResponse, $route);
        $this->assertCount(0, $result->getAppointments());
    }

    private function buildVroomResponse(array $routes = [], array $unassigned = []): array
    {
        return [
            'routes' => $routes,
            'unassigned' => $unassigned,
        ];
    }

    private function buildArrivalTimestamp(string $time): int
    {
        return Carbon::today($this->office->getTimeZone())->setTimeFromTimeString($time)->timestamp;
    }

    private function translate(array $vroomResponse, OptimizationState $sourceOptimizationState): OptimizationState
    {
        return $this->translator->translate($vroomResponse, $sourceOptimizationState, OptimizationStatus::POST);
    }
}
