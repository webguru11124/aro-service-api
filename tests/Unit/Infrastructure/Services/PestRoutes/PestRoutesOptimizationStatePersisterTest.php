<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\PestRoutes;

use App\Domain\Contracts\FeatureFlagService;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Domain\RouteOptimization\ValueObjects\WorkEvent\ExtraWork;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\SpotsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesEmployeesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesRoutesDataProcessor;
use App\Infrastructure\Services\PestRoutes\PestRoutesOptimizationStatePersister;
use App\Infrastructure\Services\PestRoutes\PestRoutesOptimizationStateResolver;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Aptive\PestRoutesSDK\Resources\Employees\EmployeeType;
use Aptive\PestRoutesSDK\Resources\Employees\Params\CreateEmployeesParams;
use Aptive\PestRoutesSDK\Resources\Employees\Params\SearchEmployeesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Params\CreateRoutesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Params\UpdateRoutesParams;
use Aptive\PestRoutesSDK\Resources\Spots\Params\SearchSpotsParams;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\ReservedTimeFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\ServiceProFactory;
use Tests\Tools\PestRoutesData\AppointmentData;
use Tests\Tools\PestRoutesData\EmployeeData;
use Tests\Tools\PestRoutesData\RouteData;
use Tests\Tools\PestRoutesData\SpotData;
use Tests\Tools\TestValue;

class PestRoutesOptimizationStatePersisterTest extends TestCase
{
    private const ROUTE_OPTIMIZATION_ENGINE_FEATURE_FLAG = 'whichRouteOptimizationEngineForOfficeIsSelected';
    private const PERSIST_OPTIMIZATION_DATA_BY_DURATION_FEATURE_FLAG = 'isPersistOptimizationDataByDurationEnabled';
    private const SERVICE_PRO_ID = 45765;
    private const UNASSIGNED_APPOINTMENT_ID = 324567;
    private const RESCHEDULE_ROUTE_ID = 438575;
    private const NON_REGULAR_ROUTE_GROUP_TITLE = 'Initial Extended Route';

    private PestRoutesOptimizationStatePersister $persister;

    private PestRoutesRoutesDataProcessor|MockInterface $mockRoutesDataProcessor;
    private PestRoutesEmployeesDataProcessor|MockInterface $mockEmployeesDataProcessor;
    private SpotsDataProcessor|MockInterface $mockSpotsDataProcessor;
    private AppointmentsDataProcessor|MockInterface $mockAppointmentsDataProcessor;
    private FeatureFlagService|MockInterface $mockFeatureFlagService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupMocks();

        $this->persister = new PestRoutesOptimizationStatePersister(
            $this->mockRoutesDataProcessor,
            $this->mockEmployeesDataProcessor,
            $this->mockSpotsDataProcessor,
            $this->mockAppointmentsDataProcessor,
            $this->mockFeatureFlagService,
        );
    }

    private function setupMocks(): void
    {
        $this->mockRoutesDataProcessor = Mockery::mock(PestRoutesRoutesDataProcessor::class);
        $this->mockEmployeesDataProcessor = Mockery::mock(PestRoutesEmployeesDataProcessor::class);
        $this->mockSpotsDataProcessor = Mockery::mock(SpotsDataProcessor::class);
        $this->mockAppointmentsDataProcessor = Mockery::mock(AppointmentsDataProcessor::class);

        $this->mockFeatureFlagService = Mockery::mock(FeatureFlagService::class);
        $this->mockFeatureFlagService->shouldReceive('getFeatureFlagStringValueForOffice')
            ->withSomeOfArgs(self::ROUTE_OPTIMIZATION_ENGINE_FEATURE_FLAG)
            ->andReturn(OptimizationEngine::VROOM->value);
    }

    /**
     * @param Collection<PestRoutesAppointment> $appointments
     *
     * @return void
     */
    private function setUpAppointmentExtraction(Collection $appointments): void
    {
        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn($appointments);
    }

    /**
     * @test
     *
     * ::persist
     */
    public function it_persists_optimized_routes(): void
    {
        /** @var ServicePro $servicePro */
        $servicePro = ServiceProFactory::make();
        $spotId = 3456576;

        /** @var Route $route */
        $route = RouteFactory::make([
            'id' => TestValue::ROUTE_ID,
            'servicePro' => $servicePro,
            'workEvents' => [
                AppointmentFactory::make([
                    'id' => TestValue::APPOINTMENT_ID,
                    'routeID' => TestValue::ROUTE_ID,
                    'timeWindow' => new TimeWindow(
                        Carbon::today(TestValue::TIME_ZONE)->hour(10)->minute(10),
                        Carbon::today(TestValue::TIME_ZONE)->hour(10)->minute(35),
                    ),
                ]),
                ReservedTimeFactory::make([
                    'id' => $spotId,
                    'timeWindow' => new TimeWindow(
                        Carbon::today(TestValue::TIME_ZONE)->hour(8),
                        Carbon::today(TestValue::TIME_ZONE)->hour(8)->minute(29),
                    ),
                ]),
            ],
        ]);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'routes' => [$route],
            'unassignedAppointments' => [],
        ]);

        $this->setUpAppointmentExtraction(AppointmentData::getTestData(
            1,
            [
                'appointmentID' => TestValue::APPOINTMENT_ID,
                'routeID' => TestValue::ROUTE_ID,
                'spotID' => $spotId,
            ]
        ));

        $date = Carbon::today(TestValue::TIME_ZONE)->toDateString();
        $this->setSpotsDataProcessorExpectations(SpotData::getTestData(
            3,
            ['spotID' => $spotId, 'routeID' => TestValue::ROUTE_ID, 'officeID' => TestValue::OFFICE_ID, 'start' => '08:00:00', 'end' => '08:29:00', 'officeTimeZone' => TestValue::TIME_ZONE, 'date' => $date],
            ['spotID' => TestValue::SPOT_ID, 'routeID' => TestValue::ROUTE_ID, 'officeID' => TestValue::OFFICE_ID, 'start' => '10:00:00', 'end' => '10:29:00', 'officeTimeZone' => TestValue::TIME_ZONE, 'date' => $date],
            ['routeID' => TestValue::ROUTE_ID, 'officeID' => TestValue::OFFICE_ID, 'start' => '11:00:00', 'end' => '11:29:00', 'blockReason' => 'Break', 'spotCapacity' => 0, 'officeTimeZone' => TestValue::TIME_ZONE, 'date' => $date],
        ));
        $this->mockSpotsDataProcessor
            ->shouldReceive('unblockMultiple')
            ->once()
            ->withArgs(function (int $officeId, Collection $spotsCollection) {
                return $officeId === TestValue::OFFICE_ID
                    && $spotsCollection->count() == 1;
            });
        $this->mockSpotsDataProcessor
            ->shouldReceive('blockMultiple')
            ->once()
            ->withArgs(function (int $officeId, Collection $spotsCollection) {
                return $officeId === TestValue::OFFICE_ID
                    && $spotsCollection->count() == 1;
            });
        $this->mockAppointmentsDataProcessor
            ->shouldReceive('assignAppointment')
            ->once()
            ->with(TestValue::OFFICE_ID, TestValue::ROUTE_ID, TestValue::APPOINTMENT_ID, TestValue::SPOT_ID);

        $this->setFeatureFlagServiceExpectations(
            self::PERSIST_OPTIMIZATION_DATA_BY_DURATION_FEATURE_FLAG,
            false,
        );

        $this->persister->persist($optimizationState);
    }

    /**
     * @test
     *
     * ::persist
     */
    public function it_persists_optimized_routes_with_feature_flag_enabled(): void
    {
        /** @var ServicePro $servicePro */
        $servicePro = ServiceProFactory::make();

        /** @var Route $route */
        $route = RouteFactory::make([
            'id' => TestValue::ROUTE_ID,
            'servicePro' => $servicePro,
            'workEvents' => [
                AppointmentFactory::make([
                    'id' => TestValue::APPOINTMENT_ID,
                    'routeID' => TestValue::ROUTE_ID,
                    'duration' => Duration::fromMinutes(45),
                ]),
                AppointmentFactory::make([
                    'id' => TestValue::APPOINTMENT_ID + 1,
                    'routeID' => TestValue::ROUTE_ID,
                    'duration' => Duration::fromMinutes(30),
                ]),
            ],
        ]);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'routes' => [$route],
            'unassignedAppointments' => [],
        ]);

        $this->setUpAppointmentExtraction(AppointmentData::getTestData(
            2,
            [
                'appointmentID' => TestValue::APPOINTMENT_ID,
                'routeID' => TestValue::ROUTE_ID,
            ],
            [
                'appointmentID' => TestValue::APPOINTMENT_ID + 1,
                'routeID' => TestValue::ROUTE_ID,
            ],
        ));

        $this->setSpotsDataProcessorExpectations(SpotData::getTestData(
            4,
            [
                'spotID' => TestValue::SPOT_ID,
                'routeID' => TestValue::ROUTE_ID,
                'officeID' => TestValue::OFFICE_ID,
                'start' => '10:00:00',
                'end' => '10:29:00',
            ],
            [
                'spotID' => TestValue::SPOT_ID + 1,
                'routeID' => TestValue::ROUTE_ID,
                'officeID' => TestValue::OFFICE_ID,
                'start' => '10:30:00',
                'end' => '10:59:00',
            ],
            [
                'spotID' => TestValue::SPOT_ID + 2,
                'routeID' => TestValue::ROUTE_ID,
                'officeID' => TestValue::OFFICE_ID,
                'start' => '11:00:00',
                'end' => '11:29:00',
                'blockReason' => 'Break',
                'spotCapacity' => 0,
            ],
            [
                'spotID' => TestValue::SPOT_ID + 3,
                'routeID' => TestValue::ROUTE_ID,
                'officeID' => TestValue::OFFICE_ID,
                'start' => '11:30:00',
                'end' => '11:59:00',
                'blockReason' => 'Lunch',
                'spotCapacity' => 0,
            ],
        ));
        $this->mockSpotsDataProcessor
            ->shouldReceive('unblockMultiple')
            ->once()
            ->withArgs(function (int $officeId, Collection $spotsCollection) {
                return $officeId === TestValue::OFFICE_ID
                    && $spotsCollection->count() == 2;
            });
        $this->mockAppointmentsDataProcessor
            ->shouldReceive('assignAppointment')
            ->twice()
            ->withArgs(function (int $officeId, int $routeId, int $appointmentId, int $spotId) {
                return $officeId === TestValue::OFFICE_ID
                    && $routeId === TestValue::ROUTE_ID
                    && in_array($appointmentId, [TestValue::APPOINTMENT_ID, TestValue::APPOINTMENT_ID + 1])
                    && in_array($spotId, [TestValue::SPOT_ID, TestValue::SPOT_ID + 2]);
            });

        $this->setFeatureFlagServiceExpectations(
            self::PERSIST_OPTIMIZATION_DATA_BY_DURATION_FEATURE_FLAG,
            true,
        );

        $this->persister->persist($optimizationState);
    }

    /**
     * @test
     *
     * ::persist
     */
    public function it_persists_optimized_routes_when_work_events_more_than_available_spots(): void
    {
        $this->setFeatureFlagServiceExpectations(
            self::PERSIST_OPTIMIZATION_DATA_BY_DURATION_FEATURE_FLAG,
            true,
        );

        /** @var ServicePro $servicePro */
        $servicePro = ServiceProFactory::make();
        $extraWork = new ExtraWork(
            timeWindow: new TimeWindow(Carbon::tomorrow()->hour(12), Carbon::tomorrow()->hour(12)->minute(30)),
            startLocation: new Coordinate(54.5432, -34.4242),
            endLocation: new Coordinate(65.2455, -65.3453),
            skills: new Collection([Skill::fromState('CA'), new Skill(Skill::INITIAL_SERVICE)]),
        );

        /** @var Route $route */
        $route = RouteFactory::make([
            'id' => TestValue::ROUTE_ID,
            'servicePro' => $servicePro,
            'workEvents' => [
                AppointmentFactory::make([
                    'id' => TestValue::APPOINTMENT_ID,
                    'routeID' => TestValue::ROUTE_ID,
                    'duration' => Duration::fromMinutes(30),
                    'timeWindow' => new TimeWindow(
                        Carbon::tomorrow()->hour(8),
                        Carbon::tomorrow()->hour(8)->minute(45),
                    ),
                ]),
                AppointmentFactory::make([
                    'id' => TestValue::APPOINTMENT_ID + 1,
                    'routeID' => TestValue::ROUTE_ID,
                    'duration' => Duration::fromMinutes(30),
                    'timeWindow' => new TimeWindow(
                        Carbon::tomorrow()->hour(9),
                        Carbon::tomorrow()->hour(9)->minute(30),
                    ),
                ]),
                AppointmentFactory::make([
                    'id' => TestValue::APPOINTMENT_ID + 2,
                    'routeID' => TestValue::ROUTE_ID,
                    'duration' => Duration::fromMinutes(20),
                    'timeWindow' => new TimeWindow(
                        Carbon::tomorrow()->hour(10),
                        Carbon::tomorrow()->hour(10)->minute(20),
                    ),
                ]),
                $extraWork,
            ],
        ]);

        $this->setUpAppointmentExtraction(AppointmentData::getTestData(
            2,
            [
                'appointmentID' => TestValue::APPOINTMENT_ID,
                'routeID' => TestValue::ROUTE_ID,
            ],
            [
                'appointmentID' => TestValue::APPOINTMENT_ID + 1,
                'routeID' => TestValue::ROUTE_ID,
            ],
        ));

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'routes' => [$route],
            'unassignedAppointments' => [],
        ]);

        $this->setSpotsDataProcessorExpectations(
            SpotData::getTestData(
                2,
                [
                    'spotID' => TestValue::SPOT_ID,
                    'routeID' => TestValue::ROUTE_ID,
                    'officeID' => TestValue::OFFICE_ID,
                    'start' => '10:00:00',
                    'end' => '10:30:00',
                ],
                [
                    'spotID' => TestValue::SPOT_ID + 1,
                    'routeID' => TestValue::ROUTE_ID,
                    'officeID' => TestValue::OFFICE_ID,
                    'start' => '10:30:00',
                    'end' => '11:00:00',
                    'blockReason' => 'Break',
                    'spotCapacity' => 0,
                ],
            )
        );

        $this->mockSpotsDataProcessor
            ->shouldReceive('unblockMultiple')
            ->once()
            ->withArgs(function (int $officeId, Collection $spotsCollection) {
                return $officeId === TestValue::OFFICE_ID
                    && $spotsCollection->count() == 1;
            });

        $this->mockAppointmentsDataProcessor
            ->shouldReceive('assignAppointment')
            ->times(3)
            ->withArgs(function (int $officeId, int $routeId, int $appointmentId, int $spotId) {
                return $officeId === TestValue::OFFICE_ID
                    && $routeId === TestValue::ROUTE_ID
                    && in_array($appointmentId, [TestValue::APPOINTMENT_ID, TestValue::APPOINTMENT_ID + 1, TestValue::APPOINTMENT_ID + 2])
                    && in_array($spotId, [TestValue::SPOT_ID, TestValue::SPOT_ID + 1]);
            });

        $this->persister->persist($optimizationState);
    }

    /**
     * @test
     *
     * ::persist
     */
    public function it_persists_unassigned_appointments_to_existing_reschedule_route(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'routes' => [],
            'unassignedAppointments' => [
                AppointmentFactory::make([
                    'id' => self::UNASSIGNED_APPOINTMENT_ID,
                    'routeID' => TestValue::ROUTE_ID,
                ]),
            ],
        ]);

        $this->setUpAppointmentExtraction(AppointmentData::getTestData(
            1,
            [
                'appointmentID' => self::UNASSIGNED_APPOINTMENT_ID,
                'routeID' => TestValue::ROUTE_ID,
            ]
        ));

        $employees = EmployeeData::getTestData(1, [
            'employeeID' => self::SERVICE_PRO_ID,
        ]);

        $this->mockEmployeesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchEmployeesParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['officeIDs'] === (string) TestValue::OFFICE_ID
                    && $array['active']
                    && $array['lname'] === PestRoutesOptimizationStateResolver::RESCHEDULE_ROUTE_EMPLOYEE_LAST_NAME
                    && $array['fname'] === PestRoutesOptimizationStateResolver::RESCHEDULE_ROUTE_EMPLOYEE_FIRST_NAME;
            })
            ->andReturn($employees);

        $rescheduleRoute = RouteData::getTestData(1, [
            'routeID' => self::RESCHEDULE_ROUTE_ID,
            'assignedTech' => self::SERVICE_PRO_ID,
        ])->first();

        $this->mockRoutesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(new Collection([$rescheduleRoute]));
        $this->mockSpotsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(SpotData::getTestData(1, ['routeID' => self::RESCHEDULE_ROUTE_ID]));
        $this->mockAppointmentsDataProcessor
            ->shouldReceive('assignAppointment')
            ->once()
            ->with(TestValue::OFFICE_ID, self::RESCHEDULE_ROUTE_ID, self::UNASSIGNED_APPOINTMENT_ID)
            ->andReturn(true);

        $this->setFeatureFlagServiceExpectations(
            self::PERSIST_OPTIMIZATION_DATA_BY_DURATION_FEATURE_FLAG,
            false,
        );

        $this->persister->persist($optimizationState);
    }

    /**
     * @test
     *
     * ::persist
     */
    public function it_creates_employee_then_reschedule_route_add_unassigned_appointments_to_it(): void
    {
        $this->setFeatureFlagServiceExpectations(
            self::PERSIST_OPTIMIZATION_DATA_BY_DURATION_FEATURE_FLAG,
            false,
        );

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'routes' => [],
            'unassignedAppointments' => [
                AppointmentFactory::make([
                    'id' => self::UNASSIGNED_APPOINTMENT_ID,
                    'routeID' => TestValue::ROUTE_ID,
                ]),
            ],
        ]);

        $this->setUpAppointmentExtraction(AppointmentData::getTestData(
            1,
            [
                'appointmentID' => self::UNASSIGNED_APPOINTMENT_ID,
                'routeID' => TestValue::ROUTE_ID,
            ]
        ));

        $this->mockEmployeesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(new Collection());
        $this->mockEmployeesDataProcessor
            ->shouldReceive('create')
            ->once()
            ->withArgs(function (int $officeId, CreateEmployeesParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['officeID'] === TestValue::OFFICE_ID
                    && $array['active']
                    && $array['type'] === EmployeeType::Technician
                    && $array['lname'] === PestRoutesOptimizationStateResolver::RESCHEDULE_ROUTE_EMPLOYEE_LAST_NAME
                    && $array['fname'] === PestRoutesOptimizationStateResolver::RESCHEDULE_ROUTE_EMPLOYEE_FIRST_NAME;
            })
            ->andReturn(true);
        $this->mockEmployeesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(EmployeeData::getTestData(1, [
                'employeeID' => self::SERVICE_PRO_ID,
            ]));

        $this->mockRoutesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(RouteData::getTestData(1, []));
        $this->mockRoutesDataProcessor
            ->shouldReceive('create')
            ->once()
            ->withArgs(function (int $officeId, CreateRoutesParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['officeID'] === TestValue::OFFICE_ID
                    && $array['autoCreateGroup']
                    && $array['assignedTech'] === self::SERVICE_PRO_ID;
            })
            ->andReturn(true);
        $this->mockRoutesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(RouteData::getTestData(1, [
                'routeID' => self::RESCHEDULE_ROUTE_ID,
                'assignedTech' => self::SERVICE_PRO_ID,
            ]));
        $this->mockRoutesDataProcessor
            ->shouldReceive('update')
            ->once()
            ->withArgs(function (int $officeId, UpdateRoutesParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['officeID'] === TestValue::OFFICE_ID
                    && $array['assignedTech'] === self::SERVICE_PRO_ID;
            })
            ->andReturn(true);
        $this->mockSpotsDataProcessor
            ->shouldReceive('unblockMultiple')
            ->once();
        $this->mockSpotsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(SpotData::getTestData());

        $this->mockAppointmentsDataProcessor
            ->shouldReceive('assignAppointment')
            ->once()
            ->with(TestValue::OFFICE_ID, self::RESCHEDULE_ROUTE_ID, self::UNASSIGNED_APPOINTMENT_ID)
            ->andReturn(true);

        $this->persister->persist($optimizationState);
    }

    /**
     * @test
     *
     * ::persist
     */
    public function it_deletes_existing_reschedule_route_when_it_does_not_have_spots_and_creates_new(): void
    {
        $this->setFeatureFlagServiceExpectations(
            self::PERSIST_OPTIMIZATION_DATA_BY_DURATION_FEATURE_FLAG,
            false,
        );

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'routes' => [],
            'unassignedAppointments' => [
                AppointmentFactory::make([
                    'id' => self::UNASSIGNED_APPOINTMENT_ID,
                    'routeID' => TestValue::ROUTE_ID,
                ]),
            ],
        ]);

        $this->setUpAppointmentExtraction(AppointmentData::getTestData(
            1,
            [
                'appointmentID' => self::UNASSIGNED_APPOINTMENT_ID,
                'routeID' => TestValue::ROUTE_ID,
            ]
        ));

        $this->mockEmployeesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(EmployeeData::getTestData(1, [
                'employeeID' => self::SERVICE_PRO_ID,
            ]));
        $this->mockRoutesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(RouteData::getTestData(1, [
                'routeID' => self::RESCHEDULE_ROUTE_ID,
                'assignedTech' => self::SERVICE_PRO_ID,
            ]));
        $this->mockSpotsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(new Collection());

        $this->mockRoutesDataProcessor
            ->shouldReceive('delete')
            ->once()
            ->with(TestValue::OFFICE_ID, self::RESCHEDULE_ROUTE_ID);
        $this->mockRoutesDataProcessor
            ->shouldReceive('create')
            ->once();
        $this->mockRoutesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(RouteData::getTestData(1, [
                'routeID' => self::RESCHEDULE_ROUTE_ID,
                'assignedTech' => self::SERVICE_PRO_ID,
            ]));
        $this->mockRoutesDataProcessor
            ->shouldReceive('update')
            ->once()
            ->andReturn(true);
        $this->mockSpotsDataProcessor
            ->shouldReceive('unblockMultiple')
            ->once();

        $this->mockAppointmentsDataProcessor
            ->shouldReceive('assignAppointment')
            ->once()
            ->with(TestValue::OFFICE_ID, self::RESCHEDULE_ROUTE_ID, self::UNASSIGNED_APPOINTMENT_ID)
            ->andReturn(true);

        $this->persister->persist($optimizationState);
    }

    /**
     * @test
     *
     * ::persist
     */
    public function it_deletes_existing_reschedule_route_from_non_regular_route_groups_and_creates_new(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'routes' => [],
            'unassignedAppointments' => [
                AppointmentFactory::make([
                    'id' => self::UNASSIGNED_APPOINTMENT_ID,
                    'routeID' => TestValue::ROUTE_ID,
                ]),
            ],
        ]);

        $this->setUpAppointmentExtraction(AppointmentData::getTestData(
            1,
            [
                'appointmentID' => self::UNASSIGNED_APPOINTMENT_ID,
                'routeID' => TestValue::ROUTE_ID,
            ],
        ));

        $this->mockEmployeesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(EmployeeData::getTestData(1, [
                'employeeID' => self::SERVICE_PRO_ID,
            ]));

        $this->mockRoutesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(RouteData::getTestData(
                2,
                [
                    'routeID' => self::RESCHEDULE_ROUTE_ID,
                    'groupTitle' => self::NON_REGULAR_ROUTE_GROUP_TITLE,
                    'assignedTech' => self::SERVICE_PRO_ID,
                ],
                []
            ));
        $this->mockRoutesDataProcessor
            ->shouldReceive('delete')
            ->once()
            ->with(TestValue::OFFICE_ID, self::RESCHEDULE_ROUTE_ID);
        $this->mockRoutesDataProcessor
            ->shouldReceive('create')
            ->once();
        $this->mockRoutesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(RouteData::getTestData(1, [
                'routeID' => self::RESCHEDULE_ROUTE_ID,
                'assignedTech' => self::SERVICE_PRO_ID,
            ]));
        $this->mockRoutesDataProcessor
            ->shouldReceive('update')
            ->once()
            ->andReturn(true);
        $this->mockSpotsDataProcessor
            ->shouldReceive('unblockMultiple')
            ->once();
        $this->mockSpotsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(SpotData::getTestData());

        $this->mockAppointmentsDataProcessor
            ->shouldReceive('assignAppointment')
            ->once()
            ->with(TestValue::OFFICE_ID, self::RESCHEDULE_ROUTE_ID, self::UNASSIGNED_APPOINTMENT_ID)
            ->andReturn(true);

        $this->setFeatureFlagServiceExpectations(
            self::PERSIST_OPTIMIZATION_DATA_BY_DURATION_FEATURE_FLAG,
            false,
        );

        $this->persister->persist($optimizationState);
    }

    /**
     * @test
     *
     * @dataProvider workEventDurationProvider
     */
    public function it_assigns_work_event_to_corresponding_spots_depending_on_duration(int $workEventDuration, int $expectedSpotCount): void
    {
        $assignedSpotIds = [TestValue::SPOT_ID + 4];

        $spots = SpotData::getTestData(
            5,
            [
                'spotID' => TestValue::SPOT_ID,
                'routeID' => TestValue::ROUTE_ID,
                'officeID' => TestValue::OFFICE_ID,
                'start' => '10:00:00',
                'end' => '10:29:00',
            ],
            [
                'spotID' => TestValue::SPOT_ID + 1,
                'routeID' => TestValue::ROUTE_ID,
                'officeID' => TestValue::OFFICE_ID,
                'start' => '10:30:00',
                'end' => '10:59:00',
            ],
            [
                'spotID' => TestValue::SPOT_ID + 2,
                'routeID' => TestValue::ROUTE_ID,
                'officeID' => TestValue::OFFICE_ID,
                'start' => '11:00:00',
                'end' => '11:29:00',
                'blockReason' => 'Break',
                'spotCapacity' => 0,
            ],
            [
                'spotID' => TestValue::SPOT_ID + 3,
                'routeID' => TestValue::ROUTE_ID,
                'officeID' => TestValue::OFFICE_ID,
                'start' => '11:30:00',
                'end' => '11:59:00',
                'blockReason' => 'Lunch',
                'spotCapacity' => 0,
            ],
            [
                'spotID' => $assignedSpotIds[0],
                'routeID' => TestValue::ROUTE_ID,
                'officeID' => TestValue::OFFICE_ID,
                'start' => '12:00:00',
                'end' => '12:29:00',
                'appointmentIds' => [TestValue::APPOINTMENT_ID],
            ]
        );

        $appointment = AppointmentFactory::make([
            'duration' => Duration::fromMinutes($workEventDuration / 60),
        ]);

        $assignedSpots = $this->invokePrivateMethod(
            $this->persister,
            'findAssignedSpots',
            [$appointment, $spots, $assignedSpotIds]
        );

        $this->assertCount($expectedSpotCount, $assignedSpots);
    }

    public static function workEventDurationProvider(): array
    {
        return [
            'less than 2400 seconds' => [2100, 1],
            '2400 seconds or more' => [2400, 2],
        ];
    }

    private function invokePrivateMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    private function setFeatureFlagServiceExpectations(string $expectedFeatureFlag, bool $isEnabled): void
    {
        $this->mockFeatureFlagService
            ->shouldReceive('isFeatureEnabledForOffice')
            ->once()
            ->withArgs(function (int $officeId, string $featureFlag) use ($expectedFeatureFlag) {
                return $officeId === TestValue::OFFICE_ID
                    && $featureFlag === $expectedFeatureFlag;
            })
            ->andReturn($isEnabled);
    }

    private function setSpotsDataProcessorExpectations(Collection $spots): void
    {
        $this->mockSpotsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchSpotsParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['officeIDs'] === [TestValue::OFFICE_ID]
                    && $array['routeIDs'] === [TestValue::ROUTE_ID]
                    && $array['skipBuild'] === '1';
            })
            ->andReturn($spots);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->persister);
        unset($this->mockRoutesDataProcessor);
        unset($this->mockEmployeesDataProcessor);
        unset($this->mockSpotsDataProcessor);
        unset($this->mockAppointmentsDataProcessor);
        unset($this->mockFeatureFlagService);
    }
}
