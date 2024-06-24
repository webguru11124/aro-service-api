<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\OptimizationRules;

use App\Domain\Contracts\FeatureFlagService;
use App\Domain\Contracts\Queries\CustomerPropertyDetailsQuery;
use App\Domain\Contracts\Queries\HistoricalAppointmentsQuery;
use App\Domain\RouteOptimization\ValueObjects\PropertyDetails;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Services\AverageDurationService;
use App\Domain\RouteOptimization\ValueObjects\ServiceDuration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Domain\RouteOptimization\Entities\Customer;
use App\Domain\RouteOptimization\OptimizationRules\SetServiceDurationWithPredictiveModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\TestValue;
use Tests\Traits\AssertRuleExecutionResultsTrait;
use Tests\Traits\RuleGetNameAndDescription;
use Tests\Tools\PestRoutesData\AppointmentData;
use Tests\Tools\Factories\ServiceProFactory;

class SetServiceDurationWithPredictiveModelTest extends TestCase
{
    use AssertRuleExecutionResultsTrait;
    use RuleGetNameAndDescription;

    private const CUSTOMER_ID = 32454;
    private const INITIAL_APPOINTMENT_DURATION = 35;
    private const REGULAR_APPOINTMENT_DURATION = 18;
    private const AVERAGE_DURATION = 33;

    private SetServiceDurationWithPredictiveModel $rule;
    private MockInterface|AverageDurationService $mockAverageDurationService;
    private MockInterface|CustomerPropertyDetailsQuery $mockCustomerPropertyDetailsQuery;
    private MockInterface|HistoricalAppointmentsQuery $mockHistoricalAppointmentsQuery;
    private MockInterface|FeatureFlagService $mockFeatureFlagService;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('aptive.initial_appointment_duration', self::INITIAL_APPOINTMENT_DURATION);
        Config::set('aptive.regular_appointment_duration', self::REGULAR_APPOINTMENT_DURATION);

        $this->mockAverageDurationService = Mockery::mock(AverageDurationService::class);
        $this->mockCustomerPropertyDetailsQuery = Mockery::mock(CustomerPropertyDetailsQuery::class);
        $this->mockHistoricalAppointmentsQuery = Mockery::mock(HistoricalAppointmentsQuery::class);
        $this->mockFeatureFlagService = Mockery::mock(FeatureFlagService::class);

        $this->rule = new SetServiceDurationWithPredictiveModel(
            $this->mockAverageDurationService,
            $this->mockCustomerPropertyDetailsQuery,
            $this->mockHistoricalAppointmentsQuery,
            $this->mockFeatureFlagService,
        );
    }

    /**
     * @test
     */
    public function it_correctly_sets_service_durations_based_on_property_details(): void
    {
        $propertyDetails = new PropertyDetails(
            100000.0,
            209000.0,
            10500.0,
        );
        $customerMock = new Customer(
            self::CUSTOMER_ID,
            $propertyDetails,
        );

        $this->mockFeatureFlagService->shouldReceive('isFeatureEnabledForOffice')
            ->andReturnTrue();

        $this->mockCustomerPropertyDetailsQuery
            ->shouldReceive('get')
            ->andReturn(collect([$customerMock]));

        $this->mockHistoricalAppointmentsQuery
            ->shouldReceive('find')
            ->andReturn(collect());

        $route = RouteFactory::make([
            'workEvents' => [AppointmentFactory::make([
                'officeId' => TestValue::OFFICE_ID,
                'customerId' => self::CUSTOMER_ID,
                'expectedArrival' => new TimeWindow(
                    Carbon::now()->setTimeFromTimeString('12:00:00'),
                    Carbon::now()->setTimeFromTimeString('18:00:00'),
                ),
            ])],
        ]);

        $optimizationState = $this->buildOptimizationState($route);

        $this->rule->process($optimizationState);

        /** @var Route $resultRoute */
        $resultRoute = $optimizationState->getRoutes()->first();
        /** @var Appointment $appointment */
        $appointment = $resultRoute->getAppointments()->first();
        $expectedServiceDuration = new ServiceDuration($propertyDetails, null, null);
        $this->assertEquals($expectedServiceDuration->getOptimumDuration()->getTotalMinutes(), $appointment->getDuration()->getTotalMinutes());
        $this->assertEquals($expectedServiceDuration->getMaximumDuration()->getTotalMinutes(), $appointment->getMaximumDuration()->getTotalMinutes());
        $this->assertEquals($expectedServiceDuration->getMinimumDuration()->getTotalMinutes(), $appointment->getMinimumDuration()->getTotalMinutes());
    }

    /**
     * @test
     */
    public function it_sets_service_duration_based_on_historical_appointment_average_duration(): void
    {
        $propertyDetails = new PropertyDetails(
            100000.0,
            209000.0,
            10500.0,
        );
        $customerMock = new Customer(
            self::CUSTOMER_ID,
            $propertyDetails,
        );

        $this->mockFeatureFlagService->shouldReceive('isFeatureEnabledForOffice')
            ->andReturnTrue();

        $this->mockCustomerPropertyDetailsQuery
            ->shouldReceive('get')
            ->andReturn(collect([$customerMock]));

        $servicePro = ServiceProFactory::make([
            'workingHours' => new TimeWindow(
                Carbon::tomorrow()->hour(TestValue::START_OF_DAY),
                Carbon::tomorrow()->hour(TestValue::END_OF_DAY),
            ),
            'routeId' => TestValue::ROUTE_ID,
        ]);

        $appointments = collect(AppointmentData::getTestData(
            1,
            [
                'routeID' => TestValue::ROUTE_ID,
                'servicedBy' => $servicePro->getId(),
                'customerID' => self::CUSTOMER_ID,
                'officeID' => TestValue::OFFICE_ID,
                'duration' => 30,
            ]
        ));

        $this->mockHistoricalAppointmentsQuery
            ->shouldReceive('find')
            ->andReturn($appointments->groupBy('customerId'));

        $route = RouteFactory::make([
            'workEvents' => [AppointmentFactory::make([
                'officeId' => TestValue::OFFICE_ID,
                'customerId' => self::CUSTOMER_ID,
                'expectedArrival' => new TimeWindow(
                    Carbon::now()->setTimeFromTimeString('12:00:00'),
                    Carbon::now()->setTimeFromTimeString('18:00:00'),
                ),
                'routeId' => TestValue::ROUTE_ID,
            ])],
            'servicePro' => $servicePro,
        ]);

        $optimizationState = $this->buildOptimizationState($route);

        $this->rule->process($optimizationState);

        /** @var Route $resultRoute */
        $resultRoute = $optimizationState->getRoutes()->first();
        /** @var Appointment $appointment */
        $appointment = $resultRoute->getAppointments()->first();
        $expectedServiceDuration = new ServiceDuration($propertyDetails, 30);
        $this->assertEquals($expectedServiceDuration->getOptimumDuration()->getTotalMinutes(), $appointment->getDuration()->getTotalMinutes());
        $this->assertEquals($expectedServiceDuration->getMaximumDuration()->getTotalMinutes(), $appointment->getMaximumDuration()->getTotalMinutes());
        $this->assertEquals($expectedServiceDuration->getMinimumDuration()->getTotalMinutes(), $appointment->getMinimumDuration()->getTotalMinutes());
    }

    /**
     * @test
     */
    public function it_correctly_sets_duration_when_feature_flag_enabled_and_no_property_details(): void
    {
        $route = RouteFactory::make([
            'workEvents' => [AppointmentFactory::make([
                'description' => 'Test Initial',
                'customerId' => self::CUSTOMER_ID,
            ])],
        ]);

        $this->mockFeatureFlagService->shouldReceive('isFeatureEnabledForOffice')
            ->andReturnTrue();

        $this->mockCustomerPropertyDetailsQuery
            ->shouldReceive('get')
            ->andReturn(collect());

        $this->mockHistoricalAppointmentsQuery
            ->shouldReceive('find')
            ->andReturn(collect());

        $optimizationState = $this->buildOptimizationState($route);

        $result = $this->rule->process($optimizationState);

        /** @var Route $resultRoute */
        $resultRoute = $optimizationState->getRoutes()->first();
        /** @var Appointment $appointment */
        $appointment = $resultRoute->getAppointments()->first();
        $this->assertEquals(self::INITIAL_APPOINTMENT_DURATION, $appointment->getDuration()->getTotalMinutes());
        $this->assertSuccessRuleResult($result);
    }

    private function buildOptimizationState(Route $route): OptimizationState
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'officeId' => TestValue::OFFICE_ID,
            'timeFrame' => new TimeWindow(
                Carbon::now()->startOfDay(),
                Carbon::now()->endOfDay(),
            ),
            'routes' => [$route],
            'unassignedAppointments' => [],
        ]);

        return $optimizationState;
    }

    protected function getClassRuleName(): string
    {
        return SetServiceDurationWithPredictiveModel::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->rule);
        unset($this->mockAverageDurationService);
        unset($this->mockCustomerPropertyDetailsQuery);
    }
}
