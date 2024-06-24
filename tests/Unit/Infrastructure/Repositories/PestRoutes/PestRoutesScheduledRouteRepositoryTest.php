<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes;

use App\Infrastructure\Exceptions\NoRegularRoutesFoundException;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\NoServiceProFoundException;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\ServiceTypesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\SpotsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesCustomersDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesEmployeesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesRoutesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\PestRoutesScheduledRouteRepository;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesSchedulingAppointmentTranslator;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesServiceProTranslator;
use Aptive\PestRoutesSDK\Filters\DateFilter;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Customers\Params\SearchCustomersParams;
use Aptive\PestRoutesSDK\Resources\Employees\Params\SearchEmployeesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Params\SearchRoutesParams;
use Aptive\PestRoutesSDK\Resources\Routes\Route as PestRoutesRoute;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\Params\SearchServiceTypesParams;
use Aptive\PestRoutesSDK\Resources\Spots\Params\SearchSpotsParams;
use Illuminate\Support\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\PestRoutesData\AppointmentData;
use Tests\Tools\PestRoutesData\CustomerData;
use Tests\Tools\PestRoutesData\EmployeeData;
use Tests\Tools\PestRoutesData\RouteData;
use Tests\Tools\PestRoutesData\SpotData;
use Tests\Tools\TestValue;

class PestRoutesScheduledRouteRepositoryTest extends TestCase
{
    private PestRoutesRoutesDataProcessor|MockInterface $mockPestRoutesRoutesDataProcessor;
    private PestRoutesEmployeesDataProcessor|MockInterface $mockPestRoutesEmployeesDataProcessor;
    private SpotsDataProcessor|MockInterface $mockSpotsDataProcessor;
    private AppointmentsDataProcessor|MockInterface $mockAppointmentsDataProcessor;
    private ServiceTypesDataProcessor|MockInterface $mockServiceTypesDataProcessor;
    private PestRoutesCustomersDataProcessor|MockInterface $mockPestRoutesCustomersDataProcessor;

    private PestRoutesScheduledRouteRepository $repository;
    private Office $office;

    protected function setUp(): void
    {
        parent::setUp();

        $this->office = OfficeFactory::make(['id' => TestValue::OFFICE_ID]);

        $this->mockPestRoutesRoutesDataProcessor = Mockery::mock(PestRoutesRoutesDataProcessor::class);
        $this->mockPestRoutesEmployeesDataProcessor = Mockery::mock(PestRoutesEmployeesDataProcessor::class);
        $this->mockSpotsDataProcessor = Mockery::mock(SpotsDataProcessor::class);
        $this->mockAppointmentsDataProcessor = Mockery::mock(AppointmentsDataProcessor::class);
        $this->mockServiceTypesDataProcessor = Mockery::mock(ServiceTypesDataProcessor::class);
        $this->mockPestRoutesCustomersDataProcessor = Mockery::mock(PestRoutesCustomersDataProcessor::class);

        $this->repository = new PestRoutesScheduledRouteRepository(
            $this->mockPestRoutesRoutesDataProcessor,
            $this->mockPestRoutesEmployeesDataProcessor,
            $this->mockSpotsDataProcessor,
            $this->mockAppointmentsDataProcessor,
            $this->mockServiceTypesDataProcessor,
            $this->mockPestRoutesCustomersDataProcessor,
            app(PestRoutesServiceProTranslator::class),
            app(PestRoutesSchedulingAppointmentTranslator::class),
        );
    }

    /**
     * @test
     */
    public function it_saves_scheduled_route_correctly(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function it_throws_exception_when_no_employee_assigned(): void
    {
        $pestRoutesRoutes = collect(RouteData::getTestData(1, [
            'assignedTech' => TestValue::EMPLOYEE_ID,
        ]));

        $this->mockPestRoutesRoutesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn($pestRoutesRoutes);

        $this->mockPestRoutesEmployeesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchEmployeesParams $params) {
                $searchParams = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $searchParams['employeeIDs'] === [TestValue::EMPLOYEE_ID]
                    && $searchParams['officeIDs'] == TestValue::OFFICE_ID;
            })
            ->andReturn(collect());

        $this->expectException(NoServiceProFoundException::class);

        $this->repository->findByOfficeIdAndDate($this->office, Carbon::today());
    }

    /**
     * @test
     */
    public function it_searches_by_office_id_and_date_correctly(): void
    {
        $date = Carbon::createFromTimeString('2023-04-17 14:00:00');

        $pestRoutesRoutes = collect(RouteData::getTestData(
            2,
            [
                'title' => 'Regular Route',
                'assignedTech' => '1',
                'lockedRoute' => '0',
            ],
            [
                'title' => 'Regular Route',
                'assignedTech' => '2',
                'lockedRoute' => '0',
            ]
        ));

        $this->mockPestRoutesRoutesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchRoutesParams $searchRoutesParams) use ($date) {
                $searchParams = $searchRoutesParams->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $searchParams['officeIDs'] === [TestValue::OFFICE_ID]
                    && $searchParams['dateStart'] === DateFilter::getPestRoutesDateString($date->clone()->startOfDay())
                    && $searchParams['dateEnd'] === DateFilter::getPestRoutesDateString($date->clone()->endOfDay())
                    && $searchParams['lockedRoute'] === '0';
            })
            ->andReturn($pestRoutesRoutes);

        $employeeIds = $pestRoutesRoutes
            ->map(fn (PestRoutesRoute $route) => $route->assignedTech)
            ->filter(fn ($assignedTech) => $assignedTech !== null)
            ->unique();

        $employees = EmployeeData::getTestData(2, [
            'employeeID' => $employeeIds[0],
            'officeID' => TestValue::OFFICE_ID,
        ], [
            'employeeID' => $employeeIds[1],
            'officeID' => TestValue::OFFICE_ID,
        ]);

        $this->mockPestRoutesEmployeesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchEmployeesParams $searchEmployeesParams) use ($employeeIds) {
                $employeeParams = $searchEmployeesParams->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $employeeParams['officeIDs'] === (string) TestValue::OFFICE_ID
                    && $employeeParams['employeeIDs'] === $employeeIds->toArray();
            })
            ->andReturn($employees);

        $customers = collect(CustomerData::getTestData(
            4,
            [
                'officeID' => TestValue::OFFICE_ID,
            ],
            [
                'officeID' => TestValue::OFFICE_ID,
            ],
            [
                'officeID' => TestValue::OFFICE_ID,
            ],
            [
                'officeID' => TestValue::OFFICE_ID,
            ],
        ));

        $appointments = collect(AppointmentData::getTestData(
            4,
            [
                'routeID' => $pestRoutesRoutes[0]->id,
                'assignedTech' => $employeeIds[0],
                'customerID' => $customers[0]->id,
                'officeID' => TestValue::OFFICE_ID,
            ],
            [
                'routeID' => $pestRoutesRoutes[0]->id,
                'assignedTech' => $employeeIds[0],
                'customerID' => $customers[1]->id,
                'officeID' => TestValue::OFFICE_ID,
            ],
            [
                'routeID' => $pestRoutesRoutes[1]->id,
                'assignedTech' => $employeeIds[1],
                'customerID' => $customers[2]->id,
                'officeID' => TestValue::OFFICE_ID,
            ],
            [
                'routeId' => $pestRoutesRoutes[1]->id,
                'serviceProId' => $employeeIds[1],
                'customerID' => $customers[3]->id,
                'officeID' => TestValue::OFFICE_ID,
            ]
        ));

        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchAppointmentsParams $searchAppointmentsParams) use ($pestRoutesRoutes) {
                $appointmentsParams = $searchAppointmentsParams->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $appointmentsParams['officeIDs'] === [TestValue::OFFICE_ID]
                    && $appointmentsParams['routeIDs'] === $pestRoutesRoutes->pluck('id')->toArray();
            })
            ->andReturn($appointments);

        $this->mockPestRoutesCustomersDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchCustomersParams $searchCustomersParams) use ($customers) {
                $customersParams = $searchCustomersParams->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $customersParams['customerIDs'] === $customers->pluck('id')->toArray()
                    && $customersParams['officeIDs'] === [TestValue::OFFICE_ID];
            })
            ->andReturn($customers);

        $pestRoutesSpots = collect(SpotData::getTestData(
            4,
            [
                'routeID' => $pestRoutesRoutes[0]->id,
            ],
            [
                'routeID' => $pestRoutesRoutes[0]->id,
            ],
            [
                'routeID' => $pestRoutesRoutes[1]->id,
            ],
            [
                'routeID' => $pestRoutesRoutes[1]->id,
            ]
        ));

        $this->mockSpotsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchSpotsParams $searchSpotsParams) use ($pestRoutesRoutes) {
                $spotsParams = $searchSpotsParams->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $spotsParams['officeIDs'] === [TestValue::OFFICE_ID]
                    && $spotsParams['routeIDs'] === $pestRoutesRoutes->pluck('id')->toArray();
            })
            ->andReturn($pestRoutesSpots);

        $this->mockServiceTypesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchServiceTypesParams $searchServiceTypesParams) use ($pestRoutesRoutes) {
                $serviceTypesParams = $searchServiceTypesParams->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $serviceTypesParams['officeIDs'] === [TestValue::OFFICE_ID];
            })
            ->andReturn($pestRoutesRoutes->pluck('serviceType')->unique());

        $result = $this->repository->findByOfficeIdAndDate($this->office, $date);

        $this->assertCount(2, $result);
    }

    /**
     * @test
     */
    public function it_filters_out_non_regular_routes(): void
    {
        $date = Carbon::createFromTimeString('2023-04-17 14:00:00');

        $initialPestRoutesRoutes = collect(RouteData::getTestData(
            2,
            [
                'title' => 'Initial Route',
                'assignedTech' => '1',
                'lockedRoute' => '0',
            ],
            [
                'title' => 'Regular Route',
                'assignedTech' => '2',
                'lockedRoute' => '0',
            ]
        ));
        $pestRoutesRoutes = collect([$initialPestRoutesRoutes->last()]);

        $this->mockPestRoutesRoutesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchRoutesParams $searchRoutesParams) use ($date) {
                $searchParams = $searchRoutesParams->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $searchParams['officeIDs'] === [TestValue::OFFICE_ID]
                    && $searchParams['dateStart'] === DateFilter::getPestRoutesDateString($date->clone()->startOfDay())
                    && $searchParams['dateEnd'] === DateFilter::getPestRoutesDateString($date->clone()->endOfDay())
                    && $searchParams['lockedRoute'] === '0';
            })
            ->andReturn($initialPestRoutesRoutes);

        $employeeIds = $pestRoutesRoutes
            ->map(fn (PestRoutesRoute $route) => $route->assignedTech)
            ->filter(fn ($assignedTech) => $assignedTech !== null)
            ->unique()
            ->toArray();

        $employees = EmployeeData::getTestData(
            1,
            [
                'employeeID' => $employeeIds[0],
                'officeID' => TestValue::OFFICE_ID,
            ]
        );

        $this->mockPestRoutesEmployeesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchEmployeesParams $searchEmployeesParams) use ($employeeIds) {
                $employeeParams = $searchEmployeesParams->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $employeeParams['officeIDs'] === (string) TestValue::OFFICE_ID
                    && array_values($employeeParams['employeeIDs']) === $employeeIds;
            })
            ->andReturn($employees);

        $customers = collect(CustomerData::getTestData(
            2,
            [
                'officeID' => TestValue::OFFICE_ID,
            ],
            [
                'officeID' => TestValue::OFFICE_ID,
            ],
        ));

        $appointments = collect(AppointmentData::getTestData(
            2,
            [
                'routeID' => $pestRoutesRoutes[0]->id,
                'assignedTech' => $employeeIds[0],
                'customerID' => $customers[0]->id,
                'officeID' => TestValue::OFFICE_ID,
            ],
            [
                'routeId' => $pestRoutesRoutes[0]->id,
                'serviceProId' => $employeeIds[0],
                'customerID' => $customers[1]->id,
                'officeID' => TestValue::OFFICE_ID,
            ]
        ));

        $this->mockAppointmentsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchAppointmentsParams $searchAppointmentsParams) use ($pestRoutesRoutes) {
                $appointmentsParams = $searchAppointmentsParams->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $appointmentsParams['officeIDs'] === [TestValue::OFFICE_ID]
                    && $appointmentsParams['routeIDs'] === $pestRoutesRoutes->pluck('id')->toArray();
            })
            ->andReturn($appointments);

        $this->mockPestRoutesCustomersDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchCustomersParams $searchCustomersParams) use ($customers) {
                $customersParams = $searchCustomersParams->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $customersParams['customerIDs'] === $customers->pluck('id')->toArray()
                    && $customersParams['officeIDs'] === [TestValue::OFFICE_ID];
            })
            ->andReturn($customers);

        $pestRoutesSpots = collect(SpotData::getTestData(
            4,
            [
                'routeID' => $pestRoutesRoutes[0]->id,
            ],
            [
                'routeID' => $pestRoutesRoutes[0]->id,
            ]
        ));

        $this->mockSpotsDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchSpotsParams $searchSpotsParams) use ($pestRoutesRoutes) {
                $spotsParams = $searchSpotsParams->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $spotsParams['officeIDs'] === [TestValue::OFFICE_ID]
                    && $spotsParams['routeIDs'] === $pestRoutesRoutes->pluck('id')->toArray();
            })
            ->andReturn($pestRoutesSpots);

        $this->mockServiceTypesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchServiceTypesParams $searchServiceTypesParams) use ($pestRoutesRoutes) {
                $serviceTypesParams = $searchServiceTypesParams->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $serviceTypesParams['officeIDs'] === [TestValue::OFFICE_ID];
            })
            ->andReturn($pestRoutesRoutes->pluck('serviceType')->unique());

        $result = $this->repository->findByOfficeIdAndDate($this->office, $date);

        $this->assertCount(1, $result);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_no_routes_received(): void
    {
        $office = OfficeFactory::make();
        $date = Carbon::createFromTimeString('2023-04-17 14:00:00');

        $pestRoutesRoutes = collect([]);

        $this->mockPestRoutesRoutesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchRoutesParams $searchRoutesParams) use ($office, $date) {
                $searchParams = $searchRoutesParams->toArray();

                return $officeId === $office->getId()
                    && $searchParams['officeIDs'] === [$office->getId()]
                    && $searchParams['dateStart'] === DateFilter::getPestRoutesDateString($date->clone()->startOfDay())
                    && $searchParams['dateEnd'] === DateFilter::getPestRoutesDateString($date->clone()->endOfDay())
                    && $searchParams['lockedRoute'] === '0';
            })
            ->andReturn($pestRoutesRoutes);

        $pestRoutesScheduledRouteRepository = $this->buildRepository();

        $this->expectException(NoRegularRoutesFoundException::class);

        $pestRoutesScheduledRouteRepository->findByOfficeIdAndDate($office, $date);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_no_regular_routes_found(): void
    {
        $office = OfficeFactory::make();
        $date = Carbon::createFromTimeString('2023-04-17 14:00:00');

        $pestRoutesRoutes = collect(RouteData::getTestData(
            2,
            [
                'title' => 'Initial Route',
                'groupTitle' => 'Regular Route',
                'assignedTech' => '1',
                'lockedRoute' => '0',
            ],
            [
                'title' => 'Regular Route',
                'groupTitle' => 'Initial Route',
                'assignedTech' => '2',
                'lockedRoute' => '0',
            ]
        ));

        $this->mockPestRoutesRoutesDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->withArgs(function (int $officeId, SearchRoutesParams $searchRoutesParams) use ($office, $date) {
                $searchParams = $searchRoutesParams->toArray();

                return $officeId === $office->getId()
                    && $searchParams['officeIDs'] === [$office->getId()]
                    && $searchParams['dateStart'] === DateFilter::getPestRoutesDateString($date->clone()->startOfDay())
                    && $searchParams['dateEnd'] === DateFilter::getPestRoutesDateString($date->clone()->endOfDay())
                    && $searchParams['lockedRoute'] === '0';
            })
            ->andReturn($pestRoutesRoutes);

        $pestRoutesScheduledRouteRepository = $this->buildRepository();

        $this->expectException(NoRegularRoutesFoundException::class);

        $pestRoutesScheduledRouteRepository->findByOfficeIdAndDate($office, $date);
    }

    /**
     * @return PestRoutesScheduledRouteRepository
     */
    private function buildRepository(): PestRoutesScheduledRouteRepository
    {
        return new PestRoutesScheduledRouteRepository(
            $this->mockPestRoutesRoutesDataProcessor,
            $this->mockPestRoutesEmployeesDataProcessor,
            $this->mockSpotsDataProcessor,
            $this->mockAppointmentsDataProcessor,
            $this->mockServiceTypesDataProcessor,
            $this->mockPestRoutesCustomersDataProcessor,
            app(PestRoutesServiceProTranslator::class),
            app(PestRoutesSchedulingAppointmentTranslator::class),
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset(
            $this->mockPestRoutesRoutesDataProcessor,
            $this->mockPestRoutesEmployeesDataProcessor,
            $this->mockSpotsDataProcessor,
            $this->mockAppointmentsDataProcessor,
            $this->mockServiceTypesDataProcessor,
            $this->mockPestRoutesCustomersDataProcessor
        );
    }
}
