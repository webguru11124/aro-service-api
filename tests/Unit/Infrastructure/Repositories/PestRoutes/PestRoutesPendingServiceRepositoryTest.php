<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes;

use App\Domain\Scheduling\Entities\Plan;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers\PestRoutesSubscriptionsDataProcessorCacheWrapper;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesSchedulingAppointmentTranslator;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesCustomerPreferencesTranslator;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesCustomerTranslator;
use Tests\TestCase;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\ServiceTypesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesAppointmentsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesCustomersDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\PestRoutesPendingServiceRepository;
use Aptive\PestRoutesSDK\Filters\DateFilter;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Customers\Params\SearchCustomersParams;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\Params\SearchServiceTypesParams;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Params\SearchSubscriptionsParams;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\Factories\Scheduling\PlanFactory;
use Tests\Tools\PestRoutesData\AppointmentData;
use Tests\Tools\PestRoutesData\CustomerData;
use Tests\Tools\PestRoutesData\ServiceTypeData;
use Tests\Tools\PestRoutesData\SubscriptionData;
use Tests\Tools\TestValue;

class PestRoutesPendingServiceRepositoryTest extends TestCase
{
    private PestRoutesPendingServiceRepository $pestRoutesPendingServiceRepository;
    private MockInterface|PestRoutesSubscriptionsDataProcessorCacheWrapper $subscriptionsDataProcessorMock;
    private MockInterface|PestRoutesAppointmentsDataProcessor $appointmentsDataProcessorMock;
    private MockInterface|PestRoutesCustomersDataProcessor $customersDataProcessorMock;
    private MockInterface|ServiceTypesDataProcessor $serviceTypeDataProcessorMock;

    private const ACTIVE_SUBSCRIPTION_FLAG = 1;
    private const SCHEDULING_PERIOD_DAYS = 14;
    private const CANCELLATION_PERIOD_DAYS = 7;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriptionsDataProcessorMock = Mockery::mock(PestRoutesSubscriptionsDataProcessorCacheWrapper::class);
        $this->appointmentsDataProcessorMock = Mockery::mock(PestRoutesAppointmentsDataProcessor::class);
        $this->customersDataProcessorMock = Mockery::mock(PestRoutesCustomersDataProcessor::class);
        $this->serviceTypeDataProcessorMock = Mockery::mock(ServiceTypesDataProcessor::class);

        $this->pestRoutesPendingServiceRepository = new PestRoutesPendingServiceRepository(
            $this->subscriptionsDataProcessorMock,
            $this->appointmentsDataProcessorMock,
            $this->customersDataProcessorMock,
            $this->serviceTypeDataProcessorMock,
            app(PestRoutesCustomerTranslator::class),
            app(PestRoutesCustomerPreferencesTranslator::class),
            app(PestRoutesSchedulingAppointmentTranslator::class),
        );
    }

    /**
     * @test
     */
    public function it_finds_pending_services_by_office_id_and_date_and_return_empty(): void
    {
        $office = OfficeFactory::make();
        $plan = PlanFactory::make();

        $this->subscriptionsDataProcessorMock
            ->shouldReceive('extractIds')
            ->andReturn([]);
        $this->serviceTypeDataProcessorMock
            ->shouldReceive('extract')
            ->andReturn(collect());

        $result = $this->pestRoutesPendingServiceRepository->findByOfficeIdAndDate($office, now(), $plan);

        $this->assertCount(0, $result);
    }

    /**
     * @test
     */
    public function it_finds_by_office_id_and_date(): void
    {
        $office = OfficeFactory::make(['location' => new Coordinate(40.7128, -74.0060)]);
        $plan = PlanFactory::make();
        $date = now(TestValue::TIME_ZONE);
        $subscriptionIds = [10001, 14002, 22585, 16958, 16395];
        //customers coordinates to make isLocationInServiceableArea return true
        $customersCoordinates = [
            ['latitude' => 40.7130, 'longitude' => -74.0058],
            ['latitude' => 42, 'longitude' => -74],
            ['latitude' => 40.7132, 'longitude' => -74.0065],
        ];
        $lastAppointmentIds = [2653145, 1659842, 2659843];
        $activeSubscriptionIds = [$subscriptionIds[0], $subscriptionIds[1], $subscriptionIds[2]];
        $activeSubscriptions = $this->generateActiveSubscriptionData(
            $office->getId(),
            $activeSubscriptionIds,
            $lastAppointmentIds
        );
        $dueDate = $this->getPlanDueDate($plan, $date);

        $this->subscriptionsDataProcessorMock
            ->shouldReceive('extractIds')
            ->once()
            ->withArgs(function (int $id, SearchSubscriptionsParams $params) use ($office, $plan) {
                $paramsArray = $params->toArray();
                $startDate = Carbon::tomorrow($office->getTimezone())->subYear();
                $endDate = Carbon::tomorrow($office->getTimezone())->addDays(self::SCHEDULING_PERIOD_DAYS);

                return $id === $office->getId()
                    && $paramsArray['officeIDs'] === [$office->getId()]
                    && $paramsArray['active'] === self::ACTIVE_SUBSCRIPTION_FLAG
                    && $paramsArray['serviceID'] === $plan->getServiceTypeId()
                    && $paramsArray['lastCompleted']->__toString() === DateFilter::between($startDate, $endDate)->__toString();
            })->andReturn($subscriptionIds);

        $this->subscriptionsDataProcessorMock
            ->shouldReceive('extractByIds')
            ->once()
            ->withArgs(function (int $id, array $subscriptionIdsParam) use ($office, $subscriptionIds) {
                return $id === $office->getId()
                    && $subscriptionIdsParam === $subscriptionIds;
            })->andReturn($activeSubscriptions);

        $this->serviceTypeDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $id, SearchServiceTypesParams $params) use ($office) {
                $paramsArray = $params->toArray();

                return $id === $office->getId()
                    && isset($paramsArray['officeIDs'])
                    && $paramsArray['officeIDs'] === [$office->getId()];
            })
            ->andReturn(collect(ServiceTypeData::getTestData(3, ['officeId' => $office->getId()])));

        //Recent appointments
        $this->appointmentsDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $id, SearchAppointmentsParams $params) use ($office, $activeSubscriptionIds, $dueDate) {
                $paramsArray = $params->toArray();

                return $id === $office->getId()
                    && isset($paramsArray['officeID'])
                    && $paramsArray['officeID'] === $office->getId()
                    && isset($paramsArray['status'])
                    && $paramsArray['status'] === [AppointmentStatus::Pending, AppointmentStatus::Completed]
                    && isset($paramsArray['subscriptionIDs'])
                    && $paramsArray['subscriptionIDs'] === $activeSubscriptionIds
                    && isset($paramsArray['date'])
                    && $paramsArray['date']->__toString() === DateFilter::greaterThanOrEqualTo($dueDate)->__toString();
            })
            ->andReturn(collect());

        // Cancelled appointments
        $this->appointmentsDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $id, SearchAppointmentsParams $params) use ($office, $activeSubscriptions, $date) {
                $paramsArray = $params->toArray();
                $customerIds = $activeSubscriptions->pluck('customerId')->toArray();
                $cancellationThresholdDateStart = $date->clone()->subDays(self::CANCELLATION_PERIOD_DAYS)->startOfDay();
                $cancellationThresholdDateEnd = $date->clone()->addDays(self::CANCELLATION_PERIOD_DAYS)->endOfDay();

                return $id === $office->getId()
                    && isset($paramsArray['officeID'])
                    && $paramsArray['officeID'] === $office->getId()
                    && isset($paramsArray['status'])
                    && $paramsArray['status'] === [AppointmentStatus::Cancelled]
                    && isset($paramsArray['customerIDs'])
                    && $paramsArray['customerIDs'] === $customerIds
                    && isset($paramsArray['date'])
                    && $paramsArray['date']->__toString() === DateFilter::between($cancellationThresholdDateStart, $cancellationThresholdDateEnd)->__toString();
            })
            ->andReturn(collect());

        $lastAppointments = $this->generateAppointments($office->getId(), $lastAppointmentIds, $activeSubscriptions->pluck('customerId')->toArray());
        $this->appointmentsDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $id, SearchAppointmentsParams $params) use ($office, $lastAppointmentIds) {
                $paramsArray = $params->toArray();

                return $id === $office->getId()
                    && isset($paramsArray['officeID'])
                    && $paramsArray['officeID'] === $office->getId()
                    && isset($paramsArray['appointmentIDs'])
                    && $paramsArray['appointmentIDs'] === $lastAppointmentIds;
            })
            ->andReturn($lastAppointments);

        $customers = $this->generateCustomerData(
            $office->getId(),
            $activeSubscriptions->pluck('customerId')->toArray(),
            $customersCoordinates,
        );
        $this->customersDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $id, SearchCustomersParams $params) use ($office, $customers) {
                $paramsArray = $params->toArray();
                $customerIds = $customers->pluck('id')->toArray();

                return $id === $office->getId()
                    && isset($paramsArray['customerIDs'])
                    && $paramsArray['customerIDs'] === $customerIds
                    && isset($paramsArray['officeIDs'])
                    && $paramsArray['officeIDs'] === [$office->getId()];
            })
            ->andReturn($customers);

        $result = $this->pestRoutesPendingServiceRepository->findByOfficeIdAndDate($office, $date, $plan);

        $this->assertCount(3, $result);
        $this->assertExpectedValues(
            $result,
            $activeSubscriptionIds,
            $customers->pluck('id')->toArray(),
            $lastAppointments
        );
    }

    /**
     * @test
     */
    public function it_finds_by_office_id_and_date_but_skip_customer_with_recent_cancellation(): void
    {
        $office = OfficeFactory::make(['location' => new Coordinate(40.7128, -74.0060)]);
        $plan = PlanFactory::make();
        $date = now();
        $subscriptionIds = [10001, 14002, 22585, 16958, 16395];
        //customers coordinates to make isLocationInServiceableArea return true
        $customersCoordinates = [
            ['latitude' => 40.7130, 'longitude' => -74.0058],
            ['latitude' => 42, 'longitude' => -74],
            ['latitude' => 40.7132, 'longitude' => -74.0065],
        ];
        $lastAppointmentIds = [2653145, 1659842, 2659843];
        $activeSubscriptionIds = [$subscriptionIds[0], $subscriptionIds[1], $subscriptionIds[2]];
        $activeSubscriptions = $this->generateActiveSubscriptionData(
            $office->getId(),
            $activeSubscriptionIds,
            $lastAppointmentIds,
        );

        $this->subscriptionsDataProcessorMock
            ->shouldReceive('extractIds')
            ->once()
            ->andReturn($subscriptionIds);

        $this->subscriptionsDataProcessorMock
            ->shouldReceive('extractByIds')
            ->once()
            ->andReturn($activeSubscriptions);

        $this->serviceTypeDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->andReturn(collect(ServiceTypeData::getTestData(3, ['officeId' => $office->getId()])));

        // Cancelled appointments
        $cancelAppointment = AppointmentData::getTestData(1, [
            'officeID' => $office->getId(),
            'customerID' => $activeSubscriptions->pluck('customerId')->toArray()[0],
        ]);
        $this->appointmentsDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->andReturn(collect($cancelAppointment));

        //Recent appointments
        $this->appointmentsDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->andReturn(collect(AppointmentData::getTestData(1)));

        $lastAppointments = $this->generateAppointments($office->getId(), $lastAppointmentIds, $activeSubscriptions->pluck('customerId')->toArray());
        $this->appointmentsDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->andReturn($lastAppointments);

        $customers = $this->generateCustomerData(
            $office->getId(),
            $activeSubscriptions->pluck('customerId')->toArray(),
            $customersCoordinates,
        );
        $this->customersDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->andReturn($customers);

        $result = $this->pestRoutesPendingServiceRepository->findByOfficeIdAndDate($office, $date, $plan);

        $this->assertCount(2, $result);
        $this->assertExpectedValues(
            $result,
            $activeSubscriptionIds,
            $customers->pluck('id')->toArray(),
            $lastAppointments
        );
    }

    /**
     * @test
     */
    public function it_finds_by_office_id_and_date_but_skip_customer_with_pending_cancellation(): void
    {
        $office = OfficeFactory::make(['location' => new Coordinate(40.7128, -74.0060)]);
        $plan = PlanFactory::make();
        $date = now();
        $subscriptionIds = [10001, 14002, 22585, 16958, 16395];
        //customers coordinates to make isLocationInServiceableArea return true and pending cancellation
        $customersCoordinates = [
            ['latitude' => 40.7130, 'longitude' => -74.0058, 'pendingCancel' => '0'],
            ['latitude' => 42, 'longitude' => -74, 'pendingCancel' => '1'],
            ['latitude' => 40.7132, 'longitude' => -74.0065, 'pendingCancel' => '0'],
        ];
        $lastAppointmentIds = [2653145, 1659842, 2659843];
        $activeSubscriptionIds = [$subscriptionIds[0], $subscriptionIds[1], $subscriptionIds[2]];
        $activeSubscriptions = $this->generateActiveSubscriptionData(
            $office->getId(),
            $activeSubscriptionIds,
            $lastAppointmentIds,
        );

        $this->subscriptionsDataProcessorMock
            ->shouldReceive('extractIds')
            ->once()
            ->andReturn($subscriptionIds);

        $this->subscriptionsDataProcessorMock
            ->shouldReceive('extractByIds')
            ->once()
            ->andReturn($activeSubscriptions);

        $this->serviceTypeDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->andReturn(collect(ServiceTypeData::getTestData(3, ['officeId' => $office->getId()])));

        // Cancelled appointments
        $this->appointmentsDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->andReturn(collect());

        //Recent appointments
        $this->appointmentsDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->andReturn(collect(AppointmentData::getTestData(1)));

        $lastAppointments = $this->generateAppointments($office->getId(), $lastAppointmentIds, $activeSubscriptions->pluck('customerId')->toArray());
        $this->appointmentsDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->andReturn($lastAppointments);

        $customers = $this->generateCustomerData(
            $office->getId(),
            $activeSubscriptions->pluck('customerId')->toArray(),
            $customersCoordinates,
        );
        $this->customersDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->andReturn($customers);

        $result = $this->pestRoutesPendingServiceRepository->findByOfficeIdAndDate($office, $date, $plan);

        $this->assertCount(2, $result);
        $this->assertExpectedValues(
            $result,
            $activeSubscriptionIds,
            $customers->pluck('id')->toArray(),
            $lastAppointments
        );
    }

    private function generateActiveSubscriptionData(int $officeId, array $activeSubscriptionIds, array $lastAppointmentIds): Collection
    {
        $activeSubscriptions = [];

        for ($i = 0; $i < count($activeSubscriptionIds); $i++) {
            $activeSubscriptions[] = SubscriptionData::getTestData(1, [
                'officeID' => $officeId,
                'subscriptionID' => $activeSubscriptionIds[$i],
                'customerID' => random_int(10000, 99999),
                'lastAppointment' => $lastAppointmentIds[$i],
            ])[0];
        }

        return collect($activeSubscriptions);
    }

    private function generateCustomerData(int $officeId, array $customerIds, array $customersCoordinates): Collection
    {
        $customers = [];

        for ($i = 0; $i < count($customerIds); $i++) {
            $customers[] = CustomerData::getTestData(1, [
                'officeID' => $officeId,
                'customerID' => $customerIds[$i],
                'lat' => $customersCoordinates[$i]['latitude'],
                'lng' => $customersCoordinates[$i]['longitude'],
                'pendingCancel' => $customersCoordinates[$i]['pendingCancel'] ?? '0',
            ])[0];
        }

        return collect($customers);
    }

    private function generateAppointments(int $officeId, array $lastAppointmentIds = [], array $customerIds = []): Collection
    {
        $appointments = [];

        for ($i = 0; $i < count($lastAppointmentIds); $i++) {
            $appointments[] = AppointmentData::getTestData(1, [
                'officeID' => $officeId,
                'appointmentID' => $lastAppointmentIds[$i],
                'customerID' => $customerIds[$i],
            ])[0];
        }

        return collect($appointments);
    }

    private function assertExpectedValues(
        Collection $pendingServices,
        array $activeExpectedSubscriptions,
        array $customerIds,
        Collection $lastAppointments,
    ): void {
        foreach ($pendingServices as $pendingService) {
            $this->assertContains($pendingService->getSubscriptionId(), $activeExpectedSubscriptions);
            $this->assertContains($pendingService->getCustomer()->getId(), $customerIds);
            $this->assertContains($pendingService->getPreviousAppointment()->getId(), $lastAppointments->pluck('id')->toArray());
        }
    }

    private function getPlanDueDate(Plan $plan, CarbonInterface $date): CarbonInterface
    {
        $intervalDays = $plan->getServiceIntervalDays($date);

        return $date->clone()->subDays($intervalDays);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->pestRoutesPendingServiceRepository);
        unset($this->subscriptionsDataProcessorMock);
        unset($this->appointmentsDataProcessorMock);
        unset($this->customersDataProcessorMock);
        unset($this->serviceTypeDataProcessorMock);
    }
}
