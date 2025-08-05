<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Tracking\Factories;

use App\Domain\Contracts\Repositories\ServicedRoutesRepository;
use App\Domain\Contracts\Services\RouteDrivingStatsService;
use App\Domain\Contracts\Services\VehicleTrackingDataService;
use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\Tracking\Entities\TreatmentState;
use App\Domain\Tracking\Entities\ServicedRoute;
use App\Domain\Tracking\Factories\TreatmentStateFactory;
use App\Domain\Tracking\ValueObjects\TreatmentStateIdentity;
use App\Infrastructure\Exceptions\NoRegularRoutesFoundException;
use App\Infrastructure\Exceptions\NoServiceProFoundException;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\Factories\Tracking\RouteDrivingStatsFactory;
use Tests\Tools\Factories\Tracking\RouteTrackingDataFactory;
use Tests\Tools\TestValue;
use Tests\Traits\RouteStatsData;

class TreatmentStateFactoryTest extends TestCase
{
    use RouteStatsData;

    private TreatmentStateFactory $treatmentStateFactory;
    private ServicedRoutesRepository|MockInterface $servicedRoutesRepositoryMock;
    private VehicleTrackingDataService|MockInterface $trackingDataServiceMock;
    private RouteDrivingStatsService|MockInterface $drivingStatsServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupMocks();

        $this->treatmentStateFactory = new TreatmentStateFactory(
            $this->servicedRoutesRepositoryMock,
            $this->trackingDataServiceMock,
            $this->drivingStatsServiceMock,
        );
    }

    private function setupMocks(): void
    {
        $this->servicedRoutesRepositoryMock = Mockery::mock(ServicedRoutesRepository::class);
        $this->trackingDataServiceMock = Mockery::mock(VehicleTrackingDataService::class);
        $this->drivingStatsServiceMock = Mockery::mock(RouteDrivingStatsService::class);
    }

    /**
     * @test
     *
     * @dataProvider exceptionProvider
     */
    public function it_throws_and_handles_exception($exceptionClass): void
    {
        $office = OfficeFactory::make([
            'id' => TestValue::OFFICE_ID,
        ]);
        $date = Carbon::parse('2024-04-05 15:30:00');
        $date->setTimezone(TestValue::TZ);

        $this->servicedRoutesRepositoryMock->shouldReceive('findByOfficeAndDate')
            ->withSomeOfArgs($office, $date)
            ->andThrow($exceptionClass);

        $result = $this->treatmentStateFactory->create($office, $date);

        $expectedResult = new TreatmentState(
            id: new TreatmentStateIdentity(officeId: $office->getId(), date: $date->clone()),
            servicedRoutes: collect(),
            trackingData: collect(),
            drivingStats: collect(),
        );
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Data provider for the test.
     *
     * @return array
     */
    public static function exceptionProvider(): array
    {
        return [
            'NoRegularRoutesFoundException' => [
                'exceptionClass' => NoRegularRoutesFoundException::class,
            ],
            'NoServiceProFoundException' => [
                'exceptionClass' => NoServiceProFoundException::class,
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider dataProvider
     */
    public function it_creates_treatment_state(
        string $inputDate,
        Collection $expectedTrackingDataCollection,
        Collection $expectedDrivingStatsCollection,
    ): void {
        $office = OfficeFactory::make([
            'id' => TestValue::OFFICE_ID,
        ]);
        $date = Carbon::parse($inputDate);
        $date->setTimezone(TestValue::TZ);

        $serviceProMock = Mockery::mock(ServicePro::class);
        $serviceProMock->shouldReceive('getWorkdayId')
            ->andReturn(TestValue::WORKDAY_ID);
        $servicedRouteMock = Mockery::mock(ServicedRoute::class);
        $servicedRouteMock->shouldReceive('getServicePro')
            ->andReturn($serviceProMock);
        $servicedRouteMock->shouldReceive('setTrackingData');
        $servicedRouteMock->shouldReceive('setDrivingStats');
        $this->servicedRoutesRepositoryMock->shouldReceive('findByOfficeAndDate')
            ->withSomeOfArgs($office, $date)
            ->andReturn(collect([$servicedRouteMock]));

        $this->trackingDataServiceMock->shouldReceive('get')
            ->withSomeOfArgs($this->getUserIds(collect([$servicedRouteMock])), $date)
            ->andReturn($expectedTrackingDataCollection);

        $this->drivingStatsServiceMock->shouldReceive('get')
            ->withSomeOfArgs($this->getUserIds(collect([$servicedRouteMock])), $date)
            ->andReturn($expectedDrivingStatsCollection);

        $result = $this->treatmentStateFactory->create($office, $date);

        $expectedResult = new TreatmentState(
            id: new TreatmentStateIdentity(officeId: $office->getId(), date: $date->clone()),
            servicedRoutes: collect([$servicedRouteMock]),
            trackingData: $expectedTrackingDataCollection,
            drivingStats: $expectedDrivingStatsCollection,
        );
        $this->assertEquals($expectedResult, $result);
    }

    public static function dataProvider(): array
    {
        return [
            'with_empty_tracking_data' => [
                'inputDate' => '2024-04-05 15:30:00',
                'expectedTrackingDataCollection' => collect([]),
                'expectedDrivingStatsCollection' => collect([RouteDrivingStatsFactory::make()]),
            ],
            'with_empty_driving_stats' => [
                'inputDate' => Carbon::now()->toDateTimeString(),
                'expectedTrackingDataCollection' => collect([RouteTrackingDataFactory::make()]),
                'expectedDrivingStatsCollection' => collect([]),
            ],
        ];
    }

    /**
     * @param Collection<ServicedRoute> $servicedRoutes
     */
    private function getUserIds(Collection $servicedRoutes): array
    {
        return $servicedRoutes->map(
            fn (ServicedRoute $route) => $route->getServicePro()->getWorkdayId()
        )->toArray();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->treatmentStateFactory);
        unset($this->servicedRoutesRepositoryMock);
        unset($this->trackingDataServiceMock);
        unset($this->drivingStatsServiceMock);
    }
}
