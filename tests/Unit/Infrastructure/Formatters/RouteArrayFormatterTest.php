<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Formatters;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Entities\WorkEvent\Meeting;
use App\Domain\RouteOptimization\Entities\WorkEvent\Travel;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkEvent;
use App\Domain\RouteOptimization\Enums\WorkEventType;
use App\Domain\RouteOptimization\Services\RouteStatisticsService;
use App\Domain\RouteOptimization\ValueObjects\EndLocation;
use App\Domain\RouteOptimization\ValueObjects\PropertyDetails;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Metric;
use App\Domain\RouteOptimization\ValueObjects\RouteStats;
use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Domain\RouteOptimization\ValueObjects\StartLocation;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Infrastructure\Formatters\RouteArrayFormatter;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\EndLocationFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\RouteOptimization\MeetingFactory;
use Tests\Tools\Factories\RouteStatsFactory;
use Tests\Tools\Factories\ServiceProFactory;
use Tests\Tools\Factories\StartLocationFactory;
use Tests\Tools\Factories\TravelFactory;
use Tests\Tools\Factories\WorkBreakFactory;
use Tests\Tools\TestValue;

class RouteArrayFormatterTest extends TestCase
{
    private const APPOINTMENT_DURATION = 25;
    private const APPOINTMENT_SETUP_DURATION = 5;
    private const APPOINTMENT_HISTORICAL_AVERAGE_DURATION = 5;

    private RouteArrayFormatter $formatter;
    private RouteStatisticsService|MockInterface $mockRouteStatisticsService;
    private Route $route;
    private RouteStatisticsService $routeStatisticsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRouteStatisticsService = Mockery::mock(RouteStatisticsService::class);
        $this->routeStatisticsService = app(RouteStatisticsService::class);
        $this->formatter = new RouteArrayFormatter($this->mockRouteStatisticsService);
    }

    /**
     * @test
     */
    public function it_can_format_route_as_an_array(): void
    {
        /** @var Appointment[] $appointments */
        $appointments = [
            AppointmentFactory::make([
                'timeWindow' => new TimeWindow(
                    Carbon::tomorrow()->hour(8)->minute(10),
                    Carbon::tomorrow()->hour(8)->minute(30)
                ),
                'description' => 'Test initial appointment',
            ]),
            AppointmentFactory::make([
                'timeWindow' => new TimeWindow(
                    Carbon::tomorrow()->hour(8)->minute(51),
                    Carbon::tomorrow()->hour(9)->minute(15)
                ),
                'description' => 'Test regular appointment',
            ]),
        ];
        $propertyDetails = new PropertyDetails(
            100000.0,
            209000.0,
            10500.0,
        );

        $appointments[0]
            ->setDuration(Duration::fromMinutes(self::APPOINTMENT_DURATION))
            ->setSetupDuration(Duration::fromMinutes(self::APPOINTMENT_SETUP_DURATION))
            ->resolveServiceDuration($propertyDetails, self::APPOINTMENT_HISTORICAL_AVERAGE_DURATION, null);
        $appointments[1]
            ->setDuration(Duration::fromMinutes(self::APPOINTMENT_DURATION))
            ->setSetupDuration(Duration::fromMinutes(self::APPOINTMENT_SETUP_DURATION));

        /** @var Travel[] $travels */
        $travels = [
            TravelFactory::make(['timeWindow' => new TimeWindow(
                Carbon::tomorrow()->hour(8)->minute(0),
                Carbon::tomorrow()->hour(8)->minute(9)
            )]),
            TravelFactory::make(['timeWindow' => new TimeWindow(
                Carbon::tomorrow()->hour(8)->minute(46),
                Carbon::tomorrow()->hour(8)->minute(50)
            )]),
            TravelFactory::make(['timeWindow' => new TimeWindow(
                Carbon::tomorrow()->hour(9)->minute(16),
                Carbon::tomorrow()->hour(9)->minute(20)
            )]),
        ];
        /** @var WorkBreak $break */
        $break = WorkBreakFactory::make([
            'timeWindow' => new TimeWindow(
                Carbon::tomorrow()->hour(8)->minute(31),
                Carbon::tomorrow()->hour(8)->minute(45)
            ),
        ]);

        /** @var Meeting $meeting */
        $meeting = MeetingFactory::make([
            'timeWindow' => new TimeWindow(
                Carbon::tomorrow()->hour(13)->minute(15),
                Carbon::tomorrow()->hour(14)->minute(15)
            ),
        ]);

        /** @var ServicePro $servicePro */
        $servicePro = ServiceProFactory::make();

        $startLocation = new StartLocation(
            Carbon::tomorrow()->hour(7)->minute(50),
            $servicePro->getStartLocation(),
        );

        $endLocation = new EndLocation(
            Carbon::tomorrow()->hour(9)->minute(21),
            $servicePro->getEndLocation(),
        );

        /** @var Route $route */
        $route = RouteFactory::make([
            'servicePro' => $servicePro,
            'workEvents' => [
                $appointments[0],
                $appointments[1],
                $travels[0],
                $travels[1],
                $travels[2],
                $break,
                $startLocation,
                $endLocation,
                $meeting,
            ],
        ]);

        /** @var RouteStats $stats */
        $stats = RouteStatsFactory::make();
        $this->mockRouteStatisticsService
            ->shouldReceive('getStats')
            ->with($route)
            ->andReturn($stats);

        $expectedFormat = [
            'id' => $route->getId(),
            'details' => [
                'route_type' => $route->getRouteType()->value,
                'start_at' => $route->getTimeWindow()->getStartAt()->toDateTimeString(),
                'end_at' => $route->getTimeWindow()->getEndAt()->toDateTimeString(),
                'start_location' => [
                    'lat' => $startLocation->getLocation()->getLatitude(),
                    'lon' => $startLocation->getLocation()->getLongitude(),
                ],
                'end_location' => [
                    'lat' => $endLocation->getLocation()->getLatitude(),
                    'lon' => $endLocation->getLocation()->getLongitude(),
                ],
                'optimization_score' => 0.0,
                'actual_capacity' => $route->getActualCapacityCount(),
                'max_capacity' => $route->getMaxCapacity(),
            ],
            'service_pro' => [
                'id' => $route->getServicePro()->getId(),
                'name' => $route->getServicePro()->getName(),
                'workday_id' => $servicePro->getWorkdayId(),
                'working_hours' => [
                    'start_at' => $servicePro->getWorkingHours()->getStartAt()->toTimeString(),
                    'end_at' => $servicePro->getWorkingHours()->getEndAt()->toTimeString(),
                ],
                'skills' => $servicePro->getSkillsWithoutPersonal()->map(fn (Skill $skill) => $skill->getLiteral())->values()->toArray(),
                'start_location' => [
                    'lat' => $servicePro->getStartLocation()->getLatitude(),
                    'lon' => $servicePro->getStartLocation()->getLongitude(),
                ],
                'end_location' => [
                    'lat' => $servicePro->getEndLocation()->getLatitude(),
                    'lon' => $servicePro->getEndLocation()->getLongitude(),
                ],
            ],
            'schedule' => [
                // Order matters here
                [
                    'work_event_type' => $startLocation->getType()->value,
                    'scheduled_time_window' => [
                        'start' => $startLocation->getTimeWindow()->getStartAt()->toDatetimeString(),
                        'end' => $startLocation->getTimeWindow()->getEndAt()->toDatetimeString(),
                    ],
                    'description' => $startLocation->getDescription(),
                    'location' => [
                        'lat' => $startLocation->getLocation()->getLatitude(),
                        'lon' => $startLocation->getLocation()->getLongitude(),
                    ],
                ],
                [
                    'work_event_type' => $travels[0]->getType()->value,
                    'scheduled_time_window' => [
                        'start' => $travels[0]->getTimeWindow()->getStartAt()->toDatetimeString(),
                        'end' => $travels[0]->getTimeWindow()->getEndAt()->toDatetimeString(),
                    ],
                    'travel_miles' => $travels[0]->getDistance()->getMiles(),
                    'description' => 'Travel',
                ],
                [
                    'appointment_id' => $appointments[0]->getId(),
                    'work_event_type' => $appointments[0]->getType()->value,
                    'scheduled_time_window' => [
                        'start' => $appointments[0]->getTimeWindow()->getStartAt()->toDatetimeString(),
                        'end' => $appointments[0]->getTimeWindow()->getEndAt()->toDatetimeString(),
                    ],
                    'priority' => $appointments[0]->getPriority(),
                    'expected_time_window' => [
                        'start' => $appointments[0]->getExpectedArrival()->getStartAt()->toDateTimeString(),
                        'end' => $appointments[0]->getExpectedArrival()->getEndAt()->toDateTimeString(),
                    ],
                    'service_duration' => $appointments[0]->getDuration()->getTotalMinutes(),
                    'setup_duration' => $appointments[0]->getSetupDuration()->getTotalMinutes(),
                    'minimum_duration' => $appointments[0]->getMinimumDuration()?->getTotalMinutes(),
                    'maximum_duration' => $appointments[0]->getMaximumDuration()?->getTotalMinutes(),
                    'is_locked' => (int) $appointments[0]->isLocked(),
                    'description' => 'Test initial appointment',
                    'location' => [
                        'lat' => $appointments[0]->getLocation()->getLatitude(),
                        'lon' => $appointments[0]->getLocation()->getLongitude(),
                    ],
                    'preferred_tech_id' => $appointments[0]->getPreferredTechId(),
                    'is_notified' => (int) $appointments[0]->isNotified(),
                    'customer_id' => $appointments[0]->getCustomerId(),
                    'skills' => $appointments[0]->getSkills()->map(fn (Skill $skill) => $skill->getLiteral())->values()->toArray(),
                ],
                [
                    'work_event_type' => $break->getType()->value,
                    'scheduled_time_window' => [
                        'start' => $break->getTimeWindow()->getStartAt()->toDatetimeString(),
                        'end' => $break->getTimeWindow()->getEndAt()->toDatetimeString(),
                    ],
                    'description' => '15 Min Break',
                    'id' => $break->getId(),
                    'expected_time_window' => [
                        'start' => $break->getExpectedArrival()->getStartAt()->toDateTimeString(),
                        'end' => $break->getExpectedArrival()->getEndAt()->toDateTimeString(),
                    ],
                ],
                [
                    'work_event_type' => $travels[1]->getType()->value,
                    'scheduled_time_window' => [
                        'start' => $travels[1]->getTimeWindow()->getStartAt()->toDatetimeString(),
                        'end' => $travels[1]->getTimeWindow()->getEndAt()->toDatetimeString(),
                    ],
                    'travel_miles' => $travels[1]->getDistance()->getMiles(),
                    'description' => 'Travel',
                ],
                [
                    'appointment_id' => $appointments[1]->getId(),
                    'work_event_type' => $appointments[1]->getType()->value,
                    'scheduled_time_window' => [
                        'start' => $appointments[1]->getTimeWindow()->getStartAt()->toDatetimeString(),
                        'end' => $appointments[1]->getTimeWindow()->getEndAt()->toDatetimeString(),
                    ],
                    'priority' => $appointments[1]->getPriority(),
                    'expected_time_window' => [
                        'start' => $appointments[1]->getExpectedArrival()->getStartAt()->toDateTimeString(),
                        'end' => $appointments[1]->getExpectedArrival()->getEndAt()->toDateTimeString(),
                    ],
                    'service_duration' => $appointments[1]->getDuration()->getTotalMinutes(),
                    'setup_duration' => $appointments[1]->getSetupDuration()->getTotalMinutes(),
                    'minimum_duration' => $appointments[1]->getMinimumDuration()?->getTotalMinutes(),
                    'maximum_duration' => $appointments[1]->getMaximumDuration()?->getTotalMinutes(),
                    'is_locked' => (int) $appointments[0]->isLocked(),
                    'description' => 'Test regular appointment',
                    'location' => [
                        'lat' => $appointments[1]->getLocation()->getLatitude(),
                        'lon' => $appointments[1]->getLocation()->getLongitude(),
                    ],
                    'preferred_tech_id' => $appointments[1]->getPreferredTechId(),
                    'is_notified' => (int) $appointments[1]->isNotified(),
                    'customer_id' => $appointments[1]->getCustomerId(),
                    'skills' => $appointments[1]->getSkills()->map(fn (Skill $skill) => $skill->getLiteral())->values()->toArray(),
                ],
                [
                    'work_event_type' => $travels[2]->getType()->value,
                    'scheduled_time_window' => [
                        'start' => $travels[2]->getTimeWindow()->getStartAt()->toDatetimeString(),
                        'end' => $travels[2]->getTimeWindow()->getEndAt()->toDatetimeString(),
                    ],
                    'travel_miles' => $travels[2]->getDistance()->getMiles(),
                    'description' => 'Travel',
                ],
                [
                    'work_event_type' => $endLocation->getType()->value,
                    'scheduled_time_window' => [
                        'start' => $endLocation->getTimeWindow()->getStartAt()->toDatetimeString(),
                        'end' => $endLocation->getTimeWindow()->getEndAt()->toDatetimeString(),
                    ],
                    'description' => $endLocation->getDescription(),
                    'location' => [
                        'lat' => $endLocation->getLocation()->getLatitude(),
                        'lon' => $endLocation->getLocation()->getLongitude(),
                    ],
                ],
                [
                    'work_event_type' => $meeting->getType()->value,
                    'scheduled_time_window' => [
                        'start' => $meeting->getTimeWindow()->getStartAt()->toDatetimeString(),
                        'end' => $meeting->getTimeWindow()->getEndAt()->toDatetimeString(),
                    ],
                    'description' => $meeting->getDescription(),
                    'location' => [
                        'lat' => $meeting->getLocation()->getLatitude(),
                        'lon' => $meeting->getLocation()->getLongitude(),
                    ],
                    'expected_time_window' => [
                        'start' => $meeting->getExpectedArrival()->getStartAt()->toDateTimeString(),
                        'end' => $meeting->getExpectedArrival()->getEndAt()->toDateTimeString(),
                    ],
                ],
            ],
            'metrics' => [],
            'stats' => $stats->toArray(),
        ];

        $formattedRoute = $this->formatter->format($route);

        $this->assertIsArray($formattedRoute);
        $this->assertEquals($expectedFormat, $formattedRoute);
    }

    /**
    * @test
    */
    public function it_formats_reschedule_route_and_unassigned_appointments(): void
    {
        $route = RouteFactory::make([
            'servicePro' => ServiceProFactory::make([
                'name' => '#Reschedule Route#',
            ]),
            'workEvents' => [
                AppointmentFactory::make([
                    'id' => TestValue::APPOINTMENT_ID + 1,
                ]),
                AppointmentFactory::make([
                    'id' => TestValue::APPOINTMENT_ID,
                ]),
            ],
        ]);

        /** @var RouteStats $stats */
        $stats = RouteStatsFactory::make();
        $this->mockRouteStatisticsService
            ->shouldReceive('getStats')
            ->with($route)
            ->andReturn($stats);

        $formattedRoute = $this->formatter->format($route);

        $this->assertIsArray($formattedRoute);
        $this->assertEquals('#Reschedule Route#', $formattedRoute['service_pro']['name']);
        $this->assertCount(2, $formattedRoute['schedule']);
        $this->assertEquals(TestValue::APPOINTMENT_ID + 1, $formattedRoute['schedule'][0]['appointment_id']);
        $this->assertEquals(TestValue::APPOINTMENT_ID, $formattedRoute['schedule'][1]['appointment_id']);
    }

    /**
     * @test
     *
     * @dataProvider workEventTypes
     */
    public function it_can_format_route(WorkEventType $workEventType, WorkEvent $workEvent): void
    {
        $this->route = RouteFactory::make([
            'workEvents' => [$workEvent],
        ]);

        $this->formatter = new RouteArrayFormatter($this->routeStatisticsService);
        $formattedState = $this->formatter->format($this->route);

        $this->assertIsArray($formattedState);
        $this->assertEquals($this->getExpectedFormat($workEventType, $workEvent), $formattedState);
    }

    /**
     * @return array<string, mixed>
     */
    public static function workEventTypes(): array
    {
        return [
            'For Appointment' => [WorkEventType::APPOINTMENT, AppointmentFactory::make()],
            'For Meeting' => [WorkEventType::MEETING, MeetingFactory::make()],
            'For Travel' => [WorkEventType::TRAVEL, TravelFactory::make()],
            'For Start Location' => [WorkEventType::START_LOCATION, StartLocationFactory::make()],
            'For End Location' => [WorkEventType::END_LOCATION, EndLocationFactory::make()],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getExpectedFormat(WorkEventType $workEventType, WorkEvent $workEvent): array
    {
        return [
            'id' => $this->route->getId(),
            'schedule' => $this->getExpectedScheduleRouteFormat($workEventType, $workEvent),
            'details' => $this->getExpectedDetailsFormat(),
            'service_pro' => $this->getExpectedServiceProFormat(),
            'metrics' => $this->getExpectedMetricsFormat(),
            'stats' => $this->routeStatisticsService->getStats($this->route)->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getExpectedScheduleRouteFormat(WorkEventType $workEventType, WorkEvent $workEvent): array
    {
        return match ($workEventType) {
            WorkEventType::APPOINTMENT => [$this->getExpectedScheduleRouteFormatForAppointment($workEvent)],
            WorkEventType::MEETING => [$this->getExpectedScheduleRouteFormatForMeeting($workEvent)],
            WorkEventType::TRAVEL => [$this->getExpectedScheduleRouteFormatForTravel($workEvent)],
            WorkEventType::END_LOCATION => [$this->getExpectedScheduleRouteFormatForLocation($workEvent)],
            WorkEventType::START_LOCATION => [$this->getExpectedScheduleRouteFormatForLocation($workEvent)],
            default => [$this->getExpectedScheduleFormatForOtherWorkEvent($workEvent)],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function getExpectedScheduleRouteFormatForAppointment(WorkEvent $workEvent): array
    {
        /** @var Appointment $workEvent */
        return array_merge(
            $this->getExpectedScheduleFormatForOtherWorkEvent($workEvent),
            [
                'location' => [
                    'lat' => $workEvent->getLocation()->getLatitude(),
                    'lon' => $workEvent->getLocation()->getLongitude(),
                ],
                'expected_time_window' => [
                    'start' => $workEvent->getExpectedArrival()?->getStartAt()->toDatetimeString(),
                    'end' => $workEvent->getExpectedArrival()?->getEndAt()->toDatetimeString(),
                ],
                'appointment_id' => $workEvent->getId(),
                'priority' => $workEvent->getPriority(),
                'setup_duration' => $workEvent->getSetupDuration()->getTotalMinutes(),
                'service_duration' => $workEvent->getDuration()->getTotalMinutes(),
                'minimum_duration' => $workEvent->getMinimumDuration()?->getTotalMinutes(),
                'maximum_duration' => $workEvent->getMaximumDuration()?->getTotalMinutes(),
                'is_locked' => (int) $workEvent->isLocked(),
                'preferred_tech_id' => $workEvent->getPreferredTechId(),
                'is_notified' => (int) $workEvent->isNotified(),
                'customer_id' => $workEvent->getCustomerId(),
                'skills' => $workEvent->getSkills()
                    ->map(fn (Skill $skill) => $skill->getLiteral())
                    ->values()
                    ->toArray(),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getExpectedScheduleRouteFormatForMeeting(WorkEvent $workEvent): array
    {
        /** @var Meeting $workEvent */
        return array_merge(
            $this->getExpectedScheduleFormatForOtherWorkEvent($workEvent),
            [
                'location' => [
                    'lat' => $workEvent->getLocation()->getLatitude(),
                    'lon' => $workEvent->getLocation()->getLongitude(),
                ],
                'expected_time_window' => [
                    'start' => $workEvent->getExpectedArrival()?->getStartAt()->toDatetimeString(),
                    'end' => $workEvent->getExpectedArrival()?->getEndAt()->toDatetimeString(),
                ],
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getExpectedScheduleRouteFormatForTravel(WorkEvent $workEvent): array
    {
        /** @var Travel $workEvent */
        return array_merge(
            $this->getExpectedScheduleFormatForOtherWorkEvent($workEvent),
            ['travel_miles' => $workEvent->getDistance()->getMiles()],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getExpectedScheduleRouteFormatForLocation(WorkEvent $workEvent): array
    {
        /** @var StartLocation|EndLocation $workEvent */
        return array_merge(
            $this->getExpectedScheduleFormatForOtherWorkEvent($workEvent),
            [
                'location' => [
                    'lat' => $workEvent->getLocation()->getLatitude(),
                    'lon' => $workEvent->getLocation()->getLongitude(),
                ],
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getExpectedScheduleFormatForOtherWorkEvent(WorkEvent $workEvent): array
    {
        return [
            'work_event_type' => $workEvent->getType()->value,
            'scheduled_time_window' => [
                'start' => $workEvent->getTimeWindow()?->getStartAt()->toDatetimeString(),
                'end' => $workEvent->getTimeWindow()?->getEndAt()->toDatetimeString(),
            ],
            'description' => $workEvent->getDescription(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getExpectedDetailsFormat(): array
    {
        return [
            'route_type' => $this->route->getRouteType()->value,
            'start_at' => $this->route->getTimeWindow()->getStartAt()->toDateTimeString(),
            'end_at' => $this->route->getTimeWindow()->getEndAt()->toDateTimeString(),
            'actual_capacity' => $this->route->getActualCapacityCount(),
            'max_capacity' => $this->route->getMaxCapacity(),
            'start_location' => [
                'lon' => $this->route->getStartLocation()->getLocation()->getLongitude(),
                'lat' => $this->route->getStartLocation()->getLocation()->getLatitude(),
            ],
            'end_location' => [
                'lon' => $this->route->getEndLocation()->getLocation()->getLongitude(),
                'lat' => $this->route->getEndLocation()->getLocation()->getLatitude(),
            ],
            'optimization_score' => $this->route->getOptimizationScore()->value(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getExpectedServiceProFormat(): array
    {
        $servicePro = $this->route->getServicePro();

        return [
            'id' => $servicePro->getId(),
            'name' => $servicePro->getName(),
            'workday_id' => $servicePro->getWorkdayId(),
            'working_hours' => [
                'start_at' => $servicePro->getWorkingHours()->getStartAt()->toTimeString(),
                'end_at' => $servicePro->getWorkingHours()->getEndAt()->toTimeString(),
            ],
            'skills' => $servicePro->getSkillsWithoutPersonal()->map(fn (Skill $skill) => $skill->getLiteral())->values()->toArray(),
            'start_location' => [
                'lat' => $servicePro->getStartLocation()->getLatitude(),
                'lon' => $servicePro->getStartLocation()->getLongitude(),
            ],
            'end_location' => [
                'lat' => $servicePro->getEndLocation()->getLatitude(),
                'lon' => $servicePro->getEndLocation()->getLongitude(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getExpectedMetricsFormat(): array
    {
        return $this->route->getMetrics()->map(function (Metric $metric) {
            return [
                'name' => $metric->getKey()->value,
                'title' => $metric->getName(),
                'weight' => $metric->getWeight()->value(),
                'value' => $metric->getValue(),
                'score' => $metric->getScore()->value(),
            ];
        })->toArray();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $this->formatter,
            $this->route,
            $this->routeStatisticsService,
            $this->mockRouteStatisticsService,
        );
    }
}
