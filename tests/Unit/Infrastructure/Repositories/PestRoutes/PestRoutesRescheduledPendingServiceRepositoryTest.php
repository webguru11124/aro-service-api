<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes;

use Illuminate\Support\Facades\Config;
use App\Domain\Contracts\Queries\PlansQuery;
use App\Domain\Scheduling\Entities\PendingService;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\NoTechFoundException;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesAppointmentRemindersDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers\PestRoutesSubscriptionsDataProcessorCacheWrapper;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\ServiceTypesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesAppointmentsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesCustomersDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesEmployeesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesRoutesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\PestRoutesRescheduledPendingServiceRepository;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesCustomerPreferencesTranslator;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesCustomerTranslator;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesSchedulingAppointmentTranslator;
use App\Infrastructure\Services\PestRoutes\Enums\ServiceType;
use App\Infrastructure\Services\PestRoutes\PestRoutesOptimizationStatePersister;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Customers\Params\SearchCustomersParams;
use Aptive\PestRoutesSDK\Resources\Employees\Params\SearchEmployeesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Params\SearchRoutesParams;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Params\SearchSubscriptionsParams;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\Factories\Scheduling\PlanFactory;
use Tests\Tools\PestRoutesData\AppointmentData;
use Tests\Tools\PestRoutesData\CustomerData;
use Tests\Tools\PestRoutesData\EmployeeData;
use Tests\Tools\PestRoutesData\RouteData;
use Tests\Tools\PestRoutesData\ServiceTypeData;
use Tests\Tools\PestRoutesData\SubscriptionData;
use Tests\Tools\TestValue;

class PestRoutesRescheduledPendingServiceRepositoryTest extends TestCase
{
    private PestRoutesRescheduledPendingServiceRepository $repository;

    private MockInterface|PlansQuery $mockPlanQuery;
    private MockInterface|PestRoutesEmployeesDataProcessor $mockEmployeesDataProcessor;
    private MockInterface|PestRoutesRoutesDataProcessor $mockRoutesDataProcessor;
    private MockInterface|PestRoutesSubscriptionsDataProcessorCacheWrapper $mockSubscriptionsDataProcessor;
    private MockInterface|PestRoutesAppointmentsDataProcessor $mockAppointmentsDataProcessor;
    private MockInterface|PestRoutesCustomersDataProcessor $mockCustomersDataProcessor;
    private MockInterface|ServiceTypesDataProcessor $mockServiceTypesDataProcessor;
    private MockInterface|PestRoutesAppointmentRemindersDataProcessor $mockAppointmentRemindersDataProcessor;

    private const SCHEDULING_PERIOD_DAYS = 14;

    private Office $office;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockPlanQuery = Mockery::mock(PlansQuery::class);
        $this->mockEmployeesDataProcessor = Mockery::mock(PestRoutesEmployeesDataProcessor::class);
        $this->mockRoutesDataProcessor = Mockery::mock(PestRoutesRoutesDataProcessor::class);
        $this->mockSubscriptionsDataProcessor = Mockery::mock(PestRoutesSubscriptionsDataProcessorCacheWrapper::class);
        $this->mockAppointmentsDataProcessor = Mockery::mock(PestRoutesAppointmentsDataProcessor::class);
        $this->mockCustomersDataProcessor = Mockery::mock(PestRoutesCustomersDataProcessor::class);
        $this->mockServiceTypesDataProcessor = Mockery::mock(ServiceTypesDataProcessor::class);
        $this->mockAppointmentRemindersDataProcessor = Mockery::mock(PestRoutesAppointmentRemindersDataProcessor::class);

        $this->office = OfficeFactory::make(['id' => TestValue::OFFICE_ID]);

        $this->repository = new PestRoutesRescheduledPendingServiceRepository(
            $this->mockPlanQuery,
            $this->mockEmployeesDataProcessor,
            $this->mockRoutesDataProcessor,
            $this->mockAppointmentsDataProcessor,
            $this->mockServiceTypesDataProcessor,
            $this->mockCustomersDataProcessor,
            $this->mockSubscriptionsDataProcessor,
            app(PestRoutesCustomerTranslator::class),
            app(PestRoutesCustomerPreferencesTranslator::class),
            app(PestRoutesSchedulingAppointmentTranslator::class),
            $this->mockAppointmentRemindersDataProcessor,
        );
    }

    /**
     * @test
     *
     * ::findByOfficeIdAndDate
     */
    public function it_throws_exception_when_no_reschedule_route_tech_found(): void
    {
        $this->setMockPlanQueryExpectations();

        $this->mockEmployeesDataProcessor
            ->shouldReceive('extract')
            ->withArgs(function (int $officeId, SearchEmployeesParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['active'] === '1'
                    && $array['fname'] === PestRoutesOptimizationStatePersister::RESCHEDULE_ROUTE_EMPLOYEE_FIRST_NAME
                    && $array['lname'] === PestRoutesOptimizationStatePersister::RESCHEDULE_ROUTE_EMPLOYEE_LAST_NAME;
            })
            ->andReturn(collect());

        $this->expectException(NoTechFoundException::class);

        $this->repository->findByOfficeIdAndDate($this->office, Carbon::today());
    }

    /**
     * @test
     *
     * ::findByOfficeIdAndDate
     */
    public function it_returns_empty_result_when_no_reschedule_routes_found(): void
    {
        $date = Carbon::today($this->office->getTimezone());
        $this->setMockPlanQueryExpectations();

        $this->mockEmployeesDataProcessor
            ->shouldReceive('extract')
            ->andReturn(collect(EmployeeData::getTestData(1, [
                'employeeID' => TestValue::EMPLOYEE_ID,
                'officeId' => TestValue::OFFICE_ID,
                'fname' => PestRoutesOptimizationStatePersister::RESCHEDULE_ROUTE_EMPLOYEE_FIRST_NAME,
                'lname' => PestRoutesOptimizationStatePersister::RESCHEDULE_ROUTE_EMPLOYEE_LAST_NAME,
            ])));

        $this->mockRoutesDataProcessor
            ->shouldReceive('extract')
            ->withArgs(function (int $officeId, SearchRoutesParams $params) use ($date) {
                $array = $params->toArray();

                return $array['officeIDs'] === [TestValue::OFFICE_ID]
                    && $array['lockedRoute'] === '0'
                    && $array['dateStart'] === $date->clone()->addDay()->startOfDay()->toDateTimeString()
                    && $array['dateEnd'] === $date->clone()->addDays(1 + self::SCHEDULING_PERIOD_DAYS)->endOfDay()->toDateTimeString();
            })
            ->andReturn(collect());

        $this->setMockServiceTypesDataProcessorExpectations();

        $result = $this->repository->findByOfficeIdAndDate($this->office, $date);

        $this->assertTrue($result->isEmpty());
    }

    /**
     * @test
     *
     * ::findByOfficeIdAndDate
     */
    public function it_returns_empty_result_when_no_appointments_found(): void
    {
        $this->setMockPlanQueryExpectations();
        $this->setMockEmployeesDataProcessorExpectations();
        $this->setMockRoutesDataProcessorExpectations();
        $this->setMockServiceTypesDataProcessorExpectations();

        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchAppointmentsParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['status'] === [AppointmentStatus::Pending]
                    && $array['routeIDs'] === [TestValue::ROUTE_ID];
            })
            ->andReturn(collect());

        $this->mockCustomersDataProcessor
            ->shouldReceive('extract')
            ->never();

        $result = $this->repository->findByOfficeIdAndDate($this->office, Carbon::today());

        $this->assertTrue($result->isEmpty());
    }

    /**
     * @test
     *
     * ::findByOfficeIdAndDate
     */
    public function it_returns_empty_result_when_no_plan_found(): void
    {
        $this->setMockPlanQueryExpectations();
        $this->setMockEmployeesDataProcessorExpectations();
        $this->setMockRoutesDataProcessorExpectations();
        $this->setMockServiceTypesDataProcessorExpectations();
        $this->setMockAppointmentsDataProcessorExpectations();
        $this->setMockCustomersDataProcessorExpectations();

        $this->mockAppointmentRemindersDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(collect());

        $this->mockSubscriptionsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchSubscriptionsParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['subscriptionIDs'] === [TestValue::SUBSCRIPTION_ID];
            })
            ->andReturn(SubscriptionData::getTestData(1, [
                'subscriptionID' => TestValue::SUBSCRIPTION_ID,
                'customerID' => TestValue::CUSTOMER_ID,
                'serviceID' => ServiceType::QUARTERLY->value, // inappropriate service type
                'officeID' => TestValue::OFFICE_ID,
            ]));

        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(collect());

        $result = $this->repository->findByOfficeIdAndDate($this->office, Carbon::today());

        $this->assertTrue($result->isEmpty());
    }

    /**
     * @test
     *
     * ::findByOfficeIdAndDate
     */
    public function it_returns_empty_result_when_no_subscription_found(): void
    {
        $this->setMockPlanQueryExpectations();
        $this->setMockEmployeesDataProcessorExpectations();
        $this->setMockRoutesDataProcessorExpectations();
        $this->setMockServiceTypesDataProcessorExpectations();
        $this->setMockCustomersDataProcessorExpectations();

        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(collect(AppointmentData::getTestData(1, [
                'appointmentID' => TestValue::APPOINTMENT_ID,
                'subscriptionID' => -1, // reservice
                'routeID' => TestValue::ROUTE_ID,
                'officeID' => TestValue::OFFICE_ID,
                'customerID' => TestValue::CUSTOMER_ID,
                'serviceTypeID' => ServiceTypeData::PRO,
                'date' => Carbon::today()->toDateString(),
                'time' => '08:00:00',
                'status' => AppointmentStatus::Pending->value,
            ])));

        $this->mockAppointmentRemindersDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(collect());

        $this->mockSubscriptionsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchSubscriptionsParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['subscriptionIDs'] === [-1];
            })
            ->andReturn(collect());

        $result = $this->repository->findByOfficeIdAndDate($this->office, Carbon::today());

        $this->assertTrue($result->isEmpty());
    }

    /**
     * @test
     *
     * ::findByOfficeIdAndDate
     */
    public function it_returns_empty_result_when_initial_appointment_found(): void
    {
        $this->setMockPlanQueryExpectations();
        $this->setMockEmployeesDataProcessorExpectations();
        $this->setMockRoutesDataProcessorExpectations();
        $this->setMockServiceTypesDataProcessorExpectations();
        $this->setMockAppointmentsDataProcessorExpectations();
        $this->setMockCustomersDataProcessorExpectations();

        $this->mockSubscriptionsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchSubscriptionsParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['subscriptionIDs'] === [TestValue::SUBSCRIPTION_ID];
            })
            ->andReturn(SubscriptionData::getTestData(1, [
                'subscriptionID' => TestValue::SUBSCRIPTION_ID,
                'customerID' => TestValue::CUSTOMER_ID,
                'serviceID' => ServiceType::BASIC->value,
                'officeID' => TestValue::OFFICE_ID,
                'lastAppointment' => '0', // no previous appointment, that means we have initial
            ]));

        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(collect());

        $this->mockAppointmentRemindersDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(collect());

        $result = $this->repository->findByOfficeIdAndDate($this->office, Carbon::today());

        $this->assertTrue($result->isEmpty());
    }

    /**
     * @test
     *
     * ::findByOfficeIdAndDate
     */
    public function it_returns_pending_services_from_reschedule_route(): void
    {
        $this->setMockPlanQueryExpectations();
        $this->setMockEmployeesDataProcessorExpectations();
        $this->setMockRoutesDataProcessorExpectations();
        $this->setMockServiceTypesDataProcessorExpectations();
        $this->setMockAppointmentsDataProcessorExpectations();

        $this->mockCustomersDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchCustomersParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['customerIDs'] === [TestValue::CUSTOMER_ID];
            })
            ->andReturn(CustomerData::getTestData(1, [
                'customerID' => TestValue::CUSTOMER_ID,
                'officeID' => TestValue::OFFICE_ID,
            ]));

        $this->mockSubscriptionsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchSubscriptionsParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['subscriptionIDs'] === [TestValue::SUBSCRIPTION_ID];
            })
            ->andReturn(SubscriptionData::getTestData(1, [
                'subscriptionID' => TestValue::SUBSCRIPTION_ID,
                'customerID' => TestValue::CUSTOMER_ID,
                'serviceID' => ServiceType::BASIC->value,
                'officeID' => TestValue::OFFICE_ID,
                'lastAppointment' => TestValue::APPOINTMENT_ID - 1,
            ]));

        $this->mockAppointmentRemindersDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(collect());

        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchAppointmentsParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['appointmentIDs'] === [TestValue::APPOINTMENT_ID - 1];
            })
            ->andReturn(collect(AppointmentData::getTestData(1, [
                'appointmentID' => TestValue::APPOINTMENT_ID - 1,
                'subscriptionID' => TestValue::SUBSCRIPTION_ID,
                'routeID' => TestValue::ROUTE_ID,
                'officeID' => TestValue::OFFICE_ID,
                'customerID' => TestValue::CUSTOMER_ID,
                'serviceTypeID' => ServiceTypeData::PRO,
                'date' => Carbon::today()->subDays(60)->toDateString(),
                'time' => '09:00:00',
                'status' => AppointmentStatus::Completed->value,
            ])));

        $result = $this->repository->findByOfficeIdAndDate($this->office, Carbon::today());

        /** @var PendingService $pendingService*/
        $pendingService = $result->first();

        $this->assertTrue($result->isNotEmpty());
        $this->assertEquals(TestValue::PLAN_DATA['serviceTypeId'], $pendingService->getPlan()->getServiceTypeId());
        $this->assertEquals(TestValue::CUSTOMER_ID, $pendingService->getCustomer()->getId());
        $this->assertEquals(TestValue::SUBSCRIPTION_ID, $pendingService->getSubscriptionId());
        $this->assertEquals(TestValue::APPOINTMENT_ID - 1, $pendingService->getPreviousAppointment()->getId());
        $this->assertEquals(TestValue::APPOINTMENT_ID, $pendingService->getNextAppointment()->getId());
    }

    /**
     * @test
     *
     * ::findByOfficeIdAndDate
     */
    public function it_returns_pending_services_from_unconfirmed_appointments(): void
    {
        Config::set('aptive.min_days_to_allow_reschedule_unconfirmed_appointments', 4);
        $scheduledDate = Carbon::today();

        $this->setMockPlanQueryExpectations();
        $this->setMockEmployeesDataProcessorExpectations();
        $this->setMockServiceTypesDataProcessorExpectations();
        $this->setMockAppointmentsDataProcessorExpectations();

        $this->mockRoutesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(collect(RouteData::getTestData(1, [
                'routeID' => TestValue::ROUTE_ID,
                'date' => $scheduledDate->addDays(4)->toDateString(),
            ])));

        $this->mockAppointmentRemindersDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(collect());

        $this->mockCustomersDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchCustomersParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['customerIDs'] === [TestValue::CUSTOMER_ID];
            })
            ->andReturn(CustomerData::getTestData(1, [
                'customerID' => TestValue::CUSTOMER_ID,
                'officeID' => TestValue::OFFICE_ID,
            ]));

        $this->mockSubscriptionsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchSubscriptionsParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['subscriptionIDs'] === [TestValue::SUBSCRIPTION_ID];
            })
            ->andReturn(SubscriptionData::getTestData(1, [
                'subscriptionID' => TestValue::SUBSCRIPTION_ID,
                'customerID' => TestValue::CUSTOMER_ID,
                'serviceID' => ServiceType::BASIC->value,
                'officeID' => TestValue::OFFICE_ID,
                'lastAppointment' => TestValue::APPOINTMENT_ID - 1,
            ]));

        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchAppointmentsParams $params) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['appointmentIDs'] === [TestValue::APPOINTMENT_ID - 1];
            })
            ->andReturn(collect(AppointmentData::getTestData(1, [
                'appointmentID' => TestValue::APPOINTMENT_ID - 1,
                'subscriptionID' => TestValue::SUBSCRIPTION_ID,
                'routeID' => TestValue::ROUTE_ID,
                'officeID' => TestValue::OFFICE_ID,
                'customerID' => TestValue::CUSTOMER_ID,
                'serviceTypeID' => ServiceTypeData::PRO,
                'date' => Carbon::today()->subDays(60)->toDateString(),
                'time' => '09:00:00',
                'status' => AppointmentStatus::Completed->value,
            ])));

        $result = $this->repository->findByOfficeIdAndDate($this->office, Carbon::today());

        /** @var PendingService $pendingService*/
        $pendingService = $result->first();

        $this->assertTrue($result->isNotEmpty());
        $this->assertEquals(TestValue::PLAN_DATA['serviceTypeId'], $pendingService->getPlan()->getServiceTypeId());
        $this->assertEquals(TestValue::CUSTOMER_ID, $pendingService->getCustomer()->getId());
        $this->assertEquals(TestValue::SUBSCRIPTION_ID, $pendingService->getSubscriptionId());
        $this->assertEquals(TestValue::APPOINTMENT_ID - 1, $pendingService->getPreviousAppointment()->getId());
        $this->assertEquals(TestValue::APPOINTMENT_ID, $pendingService->getNextAppointment()->getId());
    }

    private function setMockPlanQueryExpectations(): void
    {
        $this->mockPlanQuery
            ->shouldReceive('get')
            ->once()
            ->andReturn(collect([PlanFactory::make()]));
    }

    private function setMockEmployeesDataProcessorExpectations(): void
    {
        $this->mockEmployeesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(collect(EmployeeData::getTestData(1, [
                'employeeID' => TestValue::EMPLOYEE_ID,
            ])));
    }

    private function setMockRoutesDataProcessorExpectations(): void
    {
        $this->mockRoutesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(collect(RouteData::getTestData(1, [
                'routeID' => TestValue::ROUTE_ID,
                'assignedTech' => TestValue::EMPLOYEE_ID,
            ])));
    }

    private function setMockServiceTypesDataProcessorExpectations(): void
    {
        $this->mockServiceTypesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(ServiceTypeData::getTestData());
    }

    private function setMockAppointmentsDataProcessorExpectations(): void
    {
        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(collect(AppointmentData::getTestData(1, [
                'appointmentID' => TestValue::APPOINTMENT_ID,
                'subscriptionID' => TestValue::SUBSCRIPTION_ID,
                'routeID' => TestValue::ROUTE_ID,
                'officeID' => TestValue::OFFICE_ID,
                'customerID' => TestValue::CUSTOMER_ID,
                'serviceTypeID' => ServiceTypeData::PRO,
                'date' => Carbon::today()->toDateString(),
                'time' => '08:00:00',
                'status' => AppointmentStatus::Pending->value,
            ])));
    }

    private function setMockCustomersDataProcessorExpectations(): void
    {
        $this->mockCustomersDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn(CustomerData::getTestData(1, [
                'customerID' => TestValue::CUSTOMER_ID,
                'officeID' => TestValue::OFFICE_ID,
            ]));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->repository);
        unset($this->mockPlanQuery);
        unset($this->mockEmployeesDataProcessor);
        unset($this->mockRoutesDataProcessor);
        unset($this->mockSubscriptionsDataProcessor);
        unset($this->mockAppointmentsDataProcessor);
        unset($this->mockCustomersDataProcessor);
        unset($this->mockServiceTypesDataProcessor);
        unset($this->mockAppointmentRemindersDataProcessor);
    }
}
