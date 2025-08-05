<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\PestRoutes;

use App\Application\Events\RouteExcluded;
use App\Domain\Contracts\FeatureFlagService;
use App\Domain\Contracts\Repositories\OptimizationStateRepository;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\ReservedTime;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkEvent;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Infrastructure\Exceptions\NoAppointmentsFoundException;
use App\Infrastructure\Exceptions\NoRegularRoutesFoundException;
use App\Infrastructure\Exceptions\NoServiceProFoundException;
use App\Infrastructure\Exceptions\RoutesHaveNoCapacityException;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\SpotsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesAppointmentRemindersDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesCustomersDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesEmployeesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesRoutesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesServiceTypesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesAppointmentTranslator;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesRouteTranslator;
use App\Infrastructure\Services\PestRoutes\PestRoutesOptimizationStateResolver;
use Aptive\PestRoutesSDK\Converters\DateTimeConverter;
use Aptive\PestRoutesSDK\Resources\AppointmentReminders\AppointmentReminderStatus;
use Aptive\PestRoutesSDK\Resources\AppointmentReminders\Params\SearchAppointmentRemindersParams;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Customers\Params\SearchCustomersParams;
use Aptive\PestRoutesSDK\Resources\Employees\Params\SearchEmployeesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Params\SearchRoutesParams;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\Params\SearchServiceTypesParams;
use Aptive\PestRoutesSDK\Resources\Spots\Params\SearchSpotsParams;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\PestRoutesData\AppointmentData;
use Tests\Tools\PestRoutesData\CustomerData;
use Tests\Tools\PestRoutesData\EmployeeData;
use Tests\Tools\PestRoutesData\RouteData;
use Tests\Tools\PestRoutesData\ServiceTypeData;
use Tests\Tools\PestRoutesData\SpotData;
use Tests\Tools\TestValue;

class PestRoutesOptimizationStateResolverTest extends TestCase
{
    private const OPTIMIZATION_STATE_ID = 12345;
    private const ROUTE_OPTIMIZATION_ENGINE_FEATURE_FLAG = 'whichRouteOptimizationEngineForOfficeIsSelected';
    private const PEST_ROUTES_SKIP_BUILD_FEATURE_FLAG = 'isPestroutesSkipBuildEnabled';
    private const SERVICE_PRO_ID = 45765;
    private const CUSTOMER_ID = 876543;
    private const CUSTOMER_ID2 = 873452;
    private const CUSTOMER_ID3 = 874596;
    private const CUSTOMER_ID4 = 875479;

    private PestRoutesOptimizationStateResolver $resolver;

    private OptimizationStateRepository|MockInterface $mockOptimizationStateRepository;
    private PestRoutesRouteTranslator|MockInterface $routeTranslator;
    private PestRoutesAppointmentTranslator|MockInterface $appointmentTranslator;
    private PestRoutesRoutesDataProcessor|MockInterface $mockRoutesDataProcessor;
    private PestRoutesEmployeesDataProcessor|MockInterface $mockEmployeesDataProcessor;
    private SpotsDataProcessor|MockInterface $mockSpotsDataProcessor;
    private AppointmentsDataProcessor|MockInterface $mockAppointmentsDataProcessor;
    private PestRoutesCustomersDataProcessor|MockInterface $mockCustomersDataProcessor;
    private PestRoutesAppointmentRemindersDataProcessor|MockInterface $mockAppointmentRemindersDataProcessor;
    private PestRoutesServiceTypesDataProcessor|MockInterface $mockServiceTypesDataProcessor;
    private FeatureFlagService|MockInterface $mockFeatureFlagService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupDependencies();
        $this->setupMocks();

        $this->resolver = new PestRoutesOptimizationStateResolver(
            $this->mockOptimizationStateRepository,
            $this->routeTranslator,
            $this->appointmentTranslator,
            $this->mockRoutesDataProcessor,
            $this->mockEmployeesDataProcessor,
            $this->mockSpotsDataProcessor,
            $this->mockAppointmentsDataProcessor,
            $this->mockCustomersDataProcessor,
            $this->mockAppointmentRemindersDataProcessor,
            $this->mockServiceTypesDataProcessor,
            $this->mockFeatureFlagService,
        );
    }

    private function setupMocks(): void
    {
        $this->mockOptimizationStateRepository = Mockery::mock(OptimizationStateRepository::class);
        $this->mockRoutesDataProcessor = Mockery::mock(PestRoutesRoutesDataProcessor::class);
        $this->mockEmployeesDataProcessor = Mockery::mock(PestRoutesEmployeesDataProcessor::class);
        $this->mockSpotsDataProcessor = Mockery::mock(SpotsDataProcessor::class);
        $this->mockAppointmentsDataProcessor = Mockery::mock(AppointmentsDataProcessor::class);
        $this->mockCustomersDataProcessor = Mockery::mock(PestRoutesCustomersDataProcessor::class);
        $this->mockAppointmentRemindersDataProcessor = Mockery::mock(PestRoutesAppointmentRemindersDataProcessor::class);
        $this->mockServiceTypesDataProcessor = Mockery::mock(PestRoutesServiceTypesDataProcessor::class);

        $this->mockFeatureFlagService = Mockery::mock(FeatureFlagService::class);
        $this->mockFeatureFlagService->shouldReceive('getFeatureFlagStringValueForOffice')
            ->withSomeOfArgs(self::ROUTE_OPTIMIZATION_ENGINE_FEATURE_FLAG)
            ->andReturn(OptimizationEngine::VROOM->value);
    }

    private function setupDependencies(): void
    {
        $this->routeTranslator = app(PestRoutesRouteTranslator::class);
        $this->appointmentTranslator = app(PestRoutesAppointmentTranslator::class);
    }

    /**
     * @test
     *
     * ::resolve
     */
    public function it_throws_exception_when_no_routes_found_on_date(): void
    {
        $date = Carbon::today(DateTimeConverter::PEST_ROUTES_TIMEZONE);

        $this->setFeatureFlagServiceExpectations(self::PEST_ROUTES_SKIP_BUILD_FEATURE_FLAG, true);
        $this->setOptimizationStateRepositoryExpectations();
        $this->setRoutesDataProcessorExpectations(new Collection(), $date);

        $this->expectException(NoRegularRoutesFoundException::class);
        $this->getOptimizationState($date);
    }

    /**
     * @test
     *
     * ::resolve
     */
    public function it_throws_exception_when_there_are_reserved_spots_and_no_available_routes_found_on_date(): void
    {
        $date = Carbon::today(DateTimeConverter::PEST_ROUTES_TIMEZONE);

        $this->setFeatureFlagServiceExpectations(self::PEST_ROUTES_SKIP_BUILD_FEATURE_FLAG, true);
        $this->setOptimizationStateRepositoryExpectations();
        $this->setRoutesDataProcessorExpectations(
            RouteData::getTestData(1, ['routeID' => TestValue::ROUTE_ID]),
            $date
        );
        $this->setSpotsDataProcessorExpectations(SpotData::getTestData(1, [
            'reserved' => '1',
            'routeID' => TestValue::ROUTE_ID,
        ]));

        Log::shouldReceive('notice')->once();
        $this->expectException(NoRegularRoutesFoundException::class);
        $this->getOptimizationState($date);
    }

    /**
     * @test
     *
     * ::resolve
     */
    public function it_throws_exception_when_there_are_no_routes_found_with_api_in_who_can_schedule_option(): void
    {
        $date = Carbon::today(DateTimeConverter::PEST_ROUTES_TIMEZONE);

        $this->setFeatureFlagServiceExpectations(self::PEST_ROUTES_SKIP_BUILD_FEATURE_FLAG, true);
        $this->setOptimizationStateRepositoryExpectations();
        $this->setRoutesDataProcessorExpectations(
            RouteData::getTestData(1, ['routeID' => TestValue::ROUTE_ID, 'apiCanSchedule' => '0']),
            $date
        );
        $this->setSpotsDataProcessorExpectations(SpotData::getTestData(1, [
            'routeID' => TestValue::ROUTE_ID,
        ]));

        Log::shouldReceive('notice')->once();
        $this->expectException(NoRegularRoutesFoundException::class);
        $this->getOptimizationState($date);
    }

    /**
     * @test
     *
     * ::resolve
     */
    public function it_throws_exception_when_no_assigned_service_pro_found(): void
    {
        $date = Carbon::today(DateTimeConverter::PEST_ROUTES_TIMEZONE);

        $this->setFeatureFlagServiceExpectations(self::PEST_ROUTES_SKIP_BUILD_FEATURE_FLAG, true);
        $this->setOptimizationStateRepositoryExpectations();
        $this->setRoutesDataProcessorExpectations(
            RouteData::getTestData(1, ['routeID' => TestValue::ROUTE_ID, 'assignedTech' => null]),
            $date
        );
        $this->setSpotsDataProcessorExpectations(SpotData::getTestData(1, [
            'routeID' => TestValue::ROUTE_ID,
        ]));

        $this->expectException(NoServiceProFoundException::class);
        $this->getOptimizationState($date);
    }

    /**
     * @test
     *
     * ::resolve
     */
    public function it_throws_exception_when_no_appointments_found(): void
    {
        $date = Carbon::today(DateTimeConverter::PEST_ROUTES_TIMEZONE);

        $this->setFeatureFlagServiceExpectations(self::PEST_ROUTES_SKIP_BUILD_FEATURE_FLAG, true);
        $this->setOptimizationStateRepositoryExpectations();
        $this->setRoutesDataProcessorExpectations(
            RouteData::getTestData(1, ['routeID' => TestValue::ROUTE_ID, 'assignedTech' => self::SERVICE_PRO_ID]),
            $date
        );
        $this->setSpotsDataProcessorExpectations(SpotData::getTestData(1, [
            'routeID' => TestValue::ROUTE_ID,
        ]));
        $this->setEmployeeDataProcessorExpectations(EmployeeData::getTestData(1, [
            'employeeID' => self::SERVICE_PRO_ID,
        ]));
        $this->setAppointmentsDataProcessorExpectations(new Collection());

        $this->expectException(NoAppointmentsFoundException::class);
        $this->getOptimizationState($date);
    }

    /**
     * @test
     *
     * ::resolve
     */
    public function it_dispatches_event_when_locked_appointments_are_found(): void
    {
        $date = Carbon::today(DateTimeConverter::PEST_ROUTES_TIMEZONE);

        Event::fake();

        $this->setFeatureFlagServiceExpectations(self::PEST_ROUTES_SKIP_BUILD_FEATURE_FLAG, true);
        $this->setOptimizationStateRepositoryExpectations();
        $this->setRoutesDataProcessorExpectations(
            RouteData::getTestData(1, ['routeID' => TestValue::ROUTE_ID, 'assignedTech' => self::SERVICE_PRO_ID]),
            $date
        );
        $this->setSpotsDataProcessorExpectations(SpotData::getTestData(1, [
            'routeID' => TestValue::ROUTE_ID,
        ]));
        $this->setEmployeeDataProcessorExpectations(EmployeeData::getTestData(1, [
            'employeeID' => self::SERVICE_PRO_ID,
        ]));
        $this->setUpAppointmentDataProcessorExpectations(AppointmentData::getTestData(
            2,
            [
                'appointmentID' => TestValue::APPOINTMENT_ID,
                'routeID' => TestValue::ROUTE_ID,
                'customerID' => self::CUSTOMER_ID,
                'type' => ServiceTypeData::PREMIUM,
                'lockedBy' => 1,
            ],
            [
                'appointmentID' => TestValue::APPOINTMENT_ID + 1,
                'routeID' => TestValue::ROUTE_ID,
                'customerID' => self::CUSTOMER_ID3,
                'type' => ServiceTypeData::PREMIUM,
                'lockedBy' => 0,
            ]
        ));
        $this->mockCustomersDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchCustomersParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['officeIDs'] === [TestValue::OFFICE_ID]
                    && $array['customerIDs'] === [self::CUSTOMER_ID3]
                    && (bool) $array['includeCancellationReason'] === false
                    && (bool) $array['includeSubscriptions'] === false
                    && (bool) $array['includeCustomerFlag'] === false
                    && (bool) $array['includeAdditionalContacts'] === false
                    && (bool) $array['includePortalLogin'] === false;
            })
            ->andReturn(CustomerData::getTestData(
                2,
                ['customerID' => self::CUSTOMER_ID],
                ['customerID' => self::CUSTOMER_ID3],
            ));

        $this->mockServiceTypesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(ServiceTypeData::getTestData(1, [
                'typeID' => ServiceTypeData::PREMIUM,
            ]));

        $this->mockAppointmentRemindersDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(new Collection());

        Log::shouldReceive('notice')->once();

        $this->getOptimizationState($date);

        Event::assertDispatched(RouteExcluded::class, function ($event) use ($date) {
            return $event->routeIds === [TestValue::ROUTE_ID]
            && $event->office->getId() === TestValue::OFFICE_ID
            && $event->date->eq($date)
            && $event->reason === __('messages.notifications.route_excluded.reason.locked_appointments');
        });
    }

    /**
     * @test
     *
     * ::resolve
     */
    public function it_handles_when_appointment_service_type_not_found(): void
    {
        $date = Carbon::today(DateTimeConverter::PEST_ROUTES_TIMEZONE);

        $this->setFeatureFlagServiceExpectations(self::PEST_ROUTES_SKIP_BUILD_FEATURE_FLAG, true);
        $this->setOptimizationStateRepositoryExpectations();
        $this->setRoutesDataProcessorExpectations(
            RouteData::getTestData(1, ['routeID' => TestValue::ROUTE_ID, 'assignedTech' => self::SERVICE_PRO_ID]),
            $date
        );
        $this->setSpotsDataProcessorExpectations(SpotData::getTestData(1, [
            'routeID' => TestValue::ROUTE_ID,
        ]));
        $this->setEmployeeDataProcessorExpectations(EmployeeData::getTestData(1, [
            'employeeID' => self::SERVICE_PRO_ID,
        ]));

        $this->setUpAppointmentDataProcessorExpectations(AppointmentData::getTestData(
            1,
            [
                'appointmentID' => TestValue::APPOINTMENT_ID,
                'routeID' => TestValue::ROUTE_ID,
                'customerID' => self::CUSTOMER_ID,
                'type' => ServiceTypeData::PREMIUM,
            ]
        ));

        $this->mockCustomersDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(CustomerData::getTestData(
                1,
                ['customerID' => self::CUSTOMER_ID]
            ));

        $this->mockServiceTypesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(ServiceTypeData::getTestData(1, [
                'typeID' => ServiceTypeData::MOSQUITO,
            ]));

        $this->setAppointmentRemindersDataProcessorExpectations(new Collection());

        Log::shouldReceive('warning')->once();
        $result = $this->getOptimizationState($date);

        $this->assertTrue($result->getRoutes()->isEmpty());
    }

    /**
     * @test
     *
     * ::resolve
     */
    public function it_throws_exception_when_service_pro_is_inactive(): void
    {
        $date = Carbon::today(DateTimeConverter::PEST_ROUTES_TIMEZONE);

        $this->setFeatureFlagServiceExpectations(self::PEST_ROUTES_SKIP_BUILD_FEATURE_FLAG, true);
        $this->setOptimizationStateRepositoryExpectations();
        $this->setRoutesDataProcessorExpectations(
            RouteData::getTestData(1, ['routeID' => TestValue::ROUTE_ID, 'assignedTech' => self::SERVICE_PRO_ID]),
            $date
        );
        $this->setSpotsDataProcessorExpectations(SpotData::getTestData(1, [
            'routeID' => TestValue::ROUTE_ID,
        ]));
        $this->setEmployeeDataProcessorExpectations(new Collection());

        $this->expectException(NoServiceProFoundException::class);
        $this->getOptimizationState($date);
    }

    /**
     * @test
     *
     * ::resolve
     */
    public function it_throws_exception_when_no_routes_with_capacity_found(): void
    {
        $date = Carbon::today(DateTimeConverter::PEST_ROUTES_TIMEZONE);

        $this->setFeatureFlagServiceExpectations(self::PEST_ROUTES_SKIP_BUILD_FEATURE_FLAG, true);
        $this->setOptimizationStateRepositoryExpectations();
        $this->setRoutesDataProcessorExpectations(
            RouteData::getTestData(
                1,
                ['routeID' => TestValue::ROUTE_ID, 'assignedTech' => self::SERVICE_PRO_ID],
            ),
            $date
        );
        $this->mockSpotsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(SpotData::getTestData(
                5,
                ['routeID' => TestValue::ROUTE_ID],
                ['routeID' => TestValue::ROUTE_ID, 'blockReason' => '15 min Break', 'spotCapacity' => 0],
                ['routeID' => TestValue::ROUTE_ID],
                ['routeID' => TestValue::ROUTE_ID],
                ['routeID' => TestValue::ROUTE_ID],
            ));
        $this->setEmployeeDataProcessorExpectations(EmployeeData::getTestData(1, [
            'employeeID' => self::SERVICE_PRO_ID,
            'skills' => [],
        ]));
        $this->setUpAppointmentDataProcessorExpectations(AppointmentData::getTestData(
            2,
            [
                'appointmentID' => TestValue::APPOINTMENT_ID,
                'routeID' => TestValue::ROUTE_ID,
                'customerID' => self::CUSTOMER_ID,
                'type' => ServiceTypeData::PREMIUM,
            ],
            [
                'appointmentID' => TestValue::APPOINTMENT_ID + 1,
                'routeID' => TestValue::ROUTE_ID,
                'customerID' => self::CUSTOMER_ID3,
                'type' => ServiceTypeData::PREMIUM,
                'spotID' => '0',
            ],
        ));
        $this->mockCustomersDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(CustomerData::getTestData(
                4,
                ['customerID' => self::CUSTOMER_ID3],
                ['customerID' => self::CUSTOMER_ID],
            ));
        $this->setServiceTypesDataProcessorExpectations(ServiceTypeData::getTestData(1, [
            'typeID' => ServiceTypeData::PREMIUM,
        ]));
        $this->setAppointmentRemindersDataProcessorExpectations(new Collection());
        $this->expectException(RoutesHaveNoCapacityException::class);

        $result = $this->getOptimizationState($date);

        $this->assertTrue($result->getRoutes()->isNotEmpty());
    }

    /**
     * @test
     *
     * ::resolve
     */
    public function it_returns_optimization_state_with_new_created_appointment(): void
    {
        $date = Carbon::today(DateTimeConverter::PEST_ROUTES_TIMEZONE);
        $appointmentId3 = 894567; // new created appointment
        $testSpotId = 123456;

        $this->setFeatureFlagServiceExpectations(self::PEST_ROUTES_SKIP_BUILD_FEATURE_FLAG, true);
        $this->setOptimizationStateRepositoryExpectations();
        $this->setRoutesDataProcessorExpectations(
            RouteData::getTestData(
                1,
                ['routeID' => TestValue::ROUTE_ID, 'assignedTech' => self::SERVICE_PRO_ID],
            ),
            $date
        );
        $this->mockSpotsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(
                SpotData::getTestData(
                    5,
                    [
                        'spotID' => $testSpotId,
                        'routeID' => TestValue::ROUTE_ID,
                        'currentAppointment' => $appointmentId3,
                    ],
                    ['routeID' => TestValue::ROUTE_ID, 'blockReason' => '15 min Break', 'spotCapacity' => 0],
                    ['routeID' => TestValue::ROUTE_ID],
                    ['routeID' => TestValue::ROUTE_ID],
                    ['routeID' => TestValue::ROUTE_ID],
                )
            );

        $this->setEmployeeDataProcessorExpectations(EmployeeData::getTestData(1, [
            'employeeID' => self::SERVICE_PRO_ID,
        ]));

        $this->setUpAppointmentDataProcessorExpectations(AppointmentData::getTestData(
            2,
            [
                'appointmentID' => TestValue::APPOINTMENT_ID,
                'routeID' => TestValue::ROUTE_ID,
                'customerID' => self::CUSTOMER_ID,
                'type' => ServiceTypeData::PREMIUM,
            ],
            [
                'appointmentID' => $appointmentId3,
                'routeID' => TestValue::ROUTE_ID,
                'customerID' => self::CUSTOMER_ID3,
                'type' => ServiceTypeData::PREMIUM,
                'spotID' => '0',
            ],
        ));

        $this->mockCustomersDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchCustomersParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['officeIDs'] === [TestValue::OFFICE_ID]
                    && $array['customerIDs'] === [self::CUSTOMER_ID, self::CUSTOMER_ID3]
                    && (bool) $array['includeCancellationReason'] === false
                    && (bool) $array['includeSubscriptions'] === false
                    && (bool) $array['includeCustomerFlag'] === false
                    && (bool) $array['includeAdditionalContacts'] === false
                    && (bool) $array['includePortalLogin'] === false;
            })
            ->andReturn(CustomerData::getTestData(
                4,
                ['customerID' => self::CUSTOMER_ID3],
                ['customerID' => self::CUSTOMER_ID],
            ));

        $this->mockAppointmentsDataProcessor
            ->shouldReceive('assignAppointment')
            ->once()
            ->with(TestValue::OFFICE_ID, TestValue::ROUTE_ID, $appointmentId3, $testSpotId);

        $this->setServiceTypesDataProcessorExpectations(ServiceTypeData::getTestData(1, [
            'typeID' => ServiceTypeData::PREMIUM,
        ]));
        $this->setAppointmentRemindersDataProcessorExpectations(new Collection());

        $result = $this->getOptimizationState($date);

        $this->assertTrue($result->getRoutes()->isNotEmpty());
    }

    /**
     * @test
     *
     * ::resolve
     */
    public function it_returns_optimization_state_with_reserved_times(): void
    {
        $date = Carbon::today(DateTimeConverter::PEST_ROUTES_TIMEZONE);
        $appointmentId3 = 894567; // overbooked appointment without spot

        $this->setFeatureFlagServiceExpectations(self::PEST_ROUTES_SKIP_BUILD_FEATURE_FLAG, true);
        $this->setOptimizationStateRepositoryExpectations();
        $this->setRoutesDataProcessorExpectations(
            RouteData::getTestData(
                1,
                ['routeID' => TestValue::ROUTE_ID, 'assignedTech' => self::SERVICE_PRO_ID],
            ),
            $date
        );
        $this->mockSpotsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(
                SpotData::getTestData(
                    7,
                    ['routeID' => TestValue::ROUTE_ID],
                    ['routeID' => TestValue::ROUTE_ID, 'blockReason' => '15 min Break', 'spotCapacity' => 0],
                    ['routeID' => TestValue::ROUTE_ID, 'blockReason' => 'Lunch Break', 'spotCapacity' => 0],
                    ['routeID' => TestValue::ROUTE_ID, 'blockReason' => 'Not Working', 'start' => '15:00:00', 'end' => '15:29:00', 'spotCapacity' => 0],
                    ['routeID' => TestValue::ROUTE_ID, 'blockReason' => 'Not Working', 'start' => '15:30:00', 'end' => '15:59:00', 'spotCapacity' => 0],
                    ['routeID' => TestValue::ROUTE_ID, 'blockReason' => 'Late Start', 'start' => '16:30:00', 'end' => '16:59:00', 'spotCapacity' => 0],
                    ['routeID' => TestValue::ROUTE_ID, 'blockReason' => '', 'start' => '17:00:00', 'end' => '17:30:00', 'spotCapacity' => 0],
                )
            );

        $this->setEmployeeDataProcessorExpectations(EmployeeData::getTestData(1, [
            'employeeID' => self::SERVICE_PRO_ID,
        ]));

        $this->setUpAppointmentDataProcessorExpectations(AppointmentData::getTestData(
            2,
            [
                'appointmentID' => TestValue::APPOINTMENT_ID,
                'routeID' => TestValue::ROUTE_ID,
                'customerID' => self::CUSTOMER_ID,
                'type' => ServiceTypeData::PREMIUM,
            ],
            [
                'appointmentID' => $appointmentId3,
                'routeID' => TestValue::ROUTE_ID,
                'customerID' => self::CUSTOMER_ID3,
                'type' => ServiceTypeData::PREMIUM,
            ],
        ));

        $this->mockCustomersDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchCustomersParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['officeIDs'] === [TestValue::OFFICE_ID]
                    && $array['customerIDs'] === [self::CUSTOMER_ID, self::CUSTOMER_ID3]
                    && (bool) $array['includeCancellationReason'] === false
                    && (bool) $array['includeSubscriptions'] === false
                    && (bool) $array['includeCustomerFlag'] === false
                    && (bool) $array['includeAdditionalContacts'] === false
                    && (bool) $array['includePortalLogin'] === false;
            })
            ->andReturn(CustomerData::getTestData(
                4,
                ['customerID' => self::CUSTOMER_ID],
                ['customerID' => self::CUSTOMER_ID3],
            ));

        $this->setServiceTypesDataProcessorExpectations(ServiceTypeData::getTestData(1, [
            'typeID' => ServiceTypeData::PREMIUM,
        ]));
        $this->setAppointmentRemindersDataProcessorExpectations(new Collection());

        $result = $this->getOptimizationState($date);

        $this->assertTrue($result->getRoutes()->isNotEmpty());

        /** @var Route $firstRoute */
        $firstRoute = $result->getRoutes()->filter(fn (Route $route) => $route->getId() === TestValue::ROUTE_ID)->first();
        $this->assertNotNull($firstRoute);
        $this->assertEquals(4, $firstRoute->getAllBreaks()->count());

        $reservedTimes = $firstRoute->getAllBreaks()->filter(fn (WorkEvent $break) => $break instanceof ReservedTime)->all();
        $this->assertCount(2, $reservedTimes);

        $descriptions = array_map(fn ($break) => $break->getDescription(), $reservedTimes);

        $this->assertContains('Not Working', $descriptions);
        $this->assertContains('Late Start', $descriptions);
    }

    /**
     * @test
     *
     * ::resolve
     */
    public function it_returns_optimization_state_with_routes(): void
    {
        $date = Carbon::today(DateTimeConverter::PEST_ROUTES_TIMEZONE);
        $routeId2 = 345656;
        $routeId3 = 345699;
        $appointmentId2 = 384957;
        $appointmentId3 = 894567; // overbooked appointment without spot
        $appointmentId4 = 894589;
        $spotId1_1 = $this->faker->randomNumber(6);
        $spotId2_1 = $this->faker->randomNumber(6);
        $spotId3_1 = $this->faker->randomNumber(6);
        $spotId3_2 = $this->faker->randomNumber(6);

        $this->setFeatureFlagServiceExpectations(self::PEST_ROUTES_SKIP_BUILD_FEATURE_FLAG, true);
        $this->setOptimizationStateRepositoryExpectations();
        $this->setRoutesDataProcessorExpectations(
            RouteData::getTestData(
                3,
                ['routeID' => TestValue::ROUTE_ID, 'assignedTech' => self::SERVICE_PRO_ID],
                ['routeID' => $routeId2, 'assignedTech' => null],
                ['routeID' => $routeId3, 'assignedTech' => self::SERVICE_PRO_ID, 'apiCanSchedule' => '0']
            ),
            $date
        );
        $this->mockSpotsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(
                SpotData::getTestData(
                    7,
                    ['routeID' => TestValue::ROUTE_ID, 'spotID' => $spotId1_1],
                    ['routeID' => TestValue::ROUTE_ID, 'blockReason' => '15 min Break', 'spotCapacity' => 0],
                    ['routeID' => TestValue::ROUTE_ID, 'blockReason' => 'Lunch Break', 'spotCapacity' => 0],
                    ['routeID' => TestValue::ROUTE_ID, 'blockReason' => 'Not Working', 'spotCapacity' => 0],
                    ['routeID' => TestValue::ROUTE_ID, 'blockReason' => 'Late Start', 'spotCapacity' => 0],
                    ['routeID' => $routeId2, 'spotID' => $spotId2_1],
                    ['routeID' => $routeId3, 'spotID' => $spotId3_1],
                    ['routeID' => $routeId3, 'spotID' => $spotId3_2],
                )
            );

        $this->setEmployeeDataProcessorExpectations(EmployeeData::getTestData(1, [
            'employeeID' => self::SERVICE_PRO_ID,
        ]));

        $this->setUpAppointmentDataProcessorExpectations(AppointmentData::getTestData(
            4,
            [
                'appointmentID' => TestValue::APPOINTMENT_ID,
                'routeID' => TestValue::ROUTE_ID,
                'customerID' => self::CUSTOMER_ID,
                'type' => ServiceTypeData::PREMIUM,
                'spotID' => $spotId1_1,
            ],
            [
                'appointmentID' => $appointmentId3,
                'routeID' => TestValue::ROUTE_ID,
                'customerID' => self::CUSTOMER_ID3,
                'type' => ServiceTypeData::PREMIUM,
                'spotID' => null,
            ],
            [
                'appointmentID' => $appointmentId2,
                'routeID' => $routeId2,
                'customerID' => self::CUSTOMER_ID2,
                'type' => ServiceTypeData::PREMIUM,
                'spotID' => $spotId2_1,
            ],
            [
                'appointmentID' => $appointmentId4,
                'routeID' => $routeId3,
                'customerID' => self::CUSTOMER_ID4,
                'type' => ServiceTypeData::PREMIUM,
                'spotID' => $spotId3_1,
            ],
        ));

        $this->mockCustomersDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchCustomersParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['officeIDs'] === [TestValue::OFFICE_ID]
                    && $array['customerIDs'] === [self::CUSTOMER_ID, self::CUSTOMER_ID3, self::CUSTOMER_ID2, self::CUSTOMER_ID4]
                    && (bool) $array['includeCancellationReason'] === false
                    && (bool) $array['includeSubscriptions'] === false
                    && (bool) $array['includeCustomerFlag'] === false
                    && (bool) $array['includeAdditionalContacts'] === false
                    && (bool) $array['includePortalLogin'] === false;
            })
            ->andReturn(CustomerData::getTestData(
                4,
                ['customerID' => self::CUSTOMER_ID],
                ['customerID' => self::CUSTOMER_ID2],
                ['customerID' => self::CUSTOMER_ID3],
                ['customerID' => self::CUSTOMER_ID4]
            ));

        $this->setServiceTypesDataProcessorExpectations(ServiceTypeData::getTestData(1, [
            'typeID' => ServiceTypeData::PREMIUM,
        ]));
        $this->setAppointmentRemindersDataProcessorExpectations(new Collection());

        Log::shouldReceive('notice')->once();
        $result = $this->getOptimizationState($date);

        $this->assertTrue($result->getRoutes()->isNotEmpty());
        $this->assertEquals(1, $result->getRoutes()->count());
        $this->assertTrue($result->getRoutes()->first()->getAppointments()->isNotEmpty());
        $this->assertEquals(TestValue::APPOINTMENT_ID, $result->getRoutes()->first()->getAppointments()->first()->getId());
        $this->assertEquals(2, $result->getUnassignedAppointments()->count());
        $this->assertEquals($appointmentId2, $result->getUnassignedAppointments()->first()->getId());

        /** @var Route $firstRoute */
        $firstRoute = $result->getRoutes()->filter(fn (Route $route) => $route->getId() === TestValue::ROUTE_ID)->first();
        $this->assertNotNull($firstRoute);
        $this->assertEquals(4, $firstRoute->getAllBreaks()->count());

        $reservedTimes = $firstRoute->getAllBreaks()->filter(fn (WorkEvent $break) => $break instanceof ReservedTime)->all();
        $this->assertCount(2, $reservedTimes);

        $descriptions = array_map(fn ($break) => $break->getDescription(), $reservedTimes);

        $this->assertContains('Not Working', $descriptions);
        $this->assertContains('Late Start', $descriptions);
    }

    /**
     * @test
     *
     * ::resolve
     */
    public function it_filters_out_locked_routes_and_throws_exception(): void
    {
        $date = Carbon::today(DateTimeConverter::PEST_ROUTES_TIMEZONE);
        $appointmentId3 = 894567; // new created appointment
        $testSpotId = 123456;

        $this->setFeatureFlagServiceExpectations(self::PEST_ROUTES_SKIP_BUILD_FEATURE_FLAG, true);
        $this->setOptimizationStateRepositoryExpectations();
        $this->setRoutesDataProcessorExpectations(
            RouteData::getTestData(
                1,
                ['routeID' => TestValue::ROUTE_ID, 'assignedTech' => self::SERVICE_PRO_ID, 'lockedRoute' => '1'],
            ),
            $date
        );
        $this->mockSpotsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(
                SpotData::getTestData(
                    2,
                    [
                        'spotID' => $testSpotId,
                        'routeID' => TestValue::ROUTE_ID,
                        'currentAppointment' => $appointmentId3,
                    ],
                    ['routeID' => TestValue::ROUTE_ID, 'blockReason' => '15 min Break', 'spotCapacity' => 0],
                )
            );

        $this->expectException(NoRegularRoutesFoundException::class);
        $this->getOptimizationState($date);
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

    private function setOptimizationStateRepositoryExpectations(): void
    {
        $this->mockOptimizationStateRepository
            ->shouldReceive('getNextId')
            ->once()
            ->andReturn(self::OPTIMIZATION_STATE_ID);
    }

    private function setRoutesDataProcessorExpectations(Collection $routes, CarbonInterface $date): void
    {
        $this->mockRoutesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchRoutesParams $params) use ($date) {
                $array = $params->toArray();
                $dateString = $date->toDateString();

                return $officeId === TestValue::OFFICE_ID
                    && $array['officeIDs'] === [TestValue::OFFICE_ID]
                    && $array['dateStart'] === "$dateString 00:00:00"
                    && $array['dateEnd'] === "$dateString 23:59:59";
            })
            ->andReturn($routes);
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

    private function setEmployeeDataProcessorExpectations(Collection $employees): void
    {
        $this->mockEmployeesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchEmployeesParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['officeIDs'] === (string) TestValue::OFFICE_ID
                    && $array['employeeIDs'] === [self::SERVICE_PRO_ID];
            })
            ->andReturn($employees);
    }

    private function setAppointmentsDataProcessorExpectations(Collection $appointments): void
    {
        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchAppointmentsParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['officeIDs'] === [TestValue::OFFICE_ID]
                    && $array['status'] === AppointmentStatus::Pending
                    && $array['routeIDs'] === [TestValue::ROUTE_ID];
            })
            ->andReturn($appointments);
    }

    private function setServiceTypesDataProcessorExpectations(Collection $serviceTypes): void
    {
        $this->mockServiceTypesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchServiceTypesParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['typeIDs'] === [ServiceTypeData::PREMIUM]
                    && $array['officeIDs'] === [TestValue::OFFICE_ID];
            })
            ->andReturn($serviceTypes);
    }

    private function setAppointmentRemindersDataProcessorExpectations(Collection $reminders): void
    {
        $this->mockAppointmentRemindersDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchAppointmentRemindersParams $params) {
                $array = $params->toArray();
                $expectedStatuses = [
                    AppointmentReminderStatus::CONFIRMED_BY_OFFICE->value,
                    AppointmentReminderStatus::CONFIRMED_VIA_SMS->value,
                ];

                return str_contains((string) $array['appointmentID'], '"' . implode('","', [TestValue::APPOINTMENT_ID]) . '"')
                    && str_contains((string) $array['status'], '"' . implode('","', $expectedStatuses) . '"')
                    && $officeId === TestValue::OFFICE_ID;
            })
            ->andReturn($reminders);
    }

    /**
     * @param Collection<PestRoutesAppointment> $appointments
     *
     * @return void
     */
    private function setUpAppointmentDataProcessorExpectations(Collection $appointments): void
    {
        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn($appointments);
    }

    private function getOptimizationState(Carbon $date): OptimizationState
    {
        return $this->resolver->resolve(
            $date,
            OfficeFactory::make(['id' => TestValue::OFFICE_ID]),
            new OptimizationParams()
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->resolver);
        unset($this->routeTranslator);
        unset($this->appointmentTranslator);
        unset($this->mockOptimizationStateRepository);
        unset($this->mockRoutesDataProcessor);
        unset($this->mockEmployeesDataProcessor);
        unset($this->mockSpotsDataProcessor);
        unset($this->mockAppointmentsDataProcessor);
        unset($this->mockCustomersDataProcessor);
        unset($this->mockAppointmentRemindersDataProcessor);
        unset($this->mockServiceTypesDataProcessor);
        unset($this->mockFeatureFlagService);
    }
}
