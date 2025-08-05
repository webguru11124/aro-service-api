<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\PestRoutes\Actions;

use App\Domain\SharedKernel\Entities\Office;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Infrastructure\Dto\FindAvailableSpotsDto;
use App\Infrastructure\Queries\PestRoutes\PestRoutesRoutesCachedQuery;
use App\Infrastructure\Queries\PestRoutes\PestRoutesSpotsCachedQuery;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\OfficesDataProcessor;
use App\Infrastructure\Services\PestRoutes\Actions\FindAvailableSpots;
use App\Infrastructure\Services\PestRoutes\Entities\SpotFactory;
use App\Infrastructure\Services\PestRoutes\Enums\SpotType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\PestRoutesData\RouteData;
use Tests\Tools\PestRoutesData\SpotData;
use Tests\Tools\TestValue;

class FindAvailableSpotsTest extends TestCase
{
    private const int BUCKET_ROUTE_ID = TestValue::ROUTE_ID;
    private const int REGULAR_ROUTE_ID = TestValue::ROUTE_ID + 1;
    private const string START_DATE = '2024-01-01';
    private const string END_DATE = '2024-01-05';
    private const int DISTANCE_THRESHOLD = 5;

    private FindAvailableSpots $action;
    private Office $office;
    private Coordinate $coordinate;

    private PestRoutesSpotsCachedQuery|MockInterface $mockSpotsCachedQuery;
    private PestRoutesRoutesCachedQuery|MockInterface $mockRoutesCachedQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->officesDataProcessorMock = \Mockery::mock(OfficesDataProcessor::class);
        $this->mockSpotsCachedQuery = \Mockery::mock(PestRoutesSpotsCachedQuery::class);
        $this->mockRoutesCachedQuery = \Mockery::mock(PestRoutesRoutesCachedQuery::class);

        $this->action = new FindAvailableSpots(
            $this->mockSpotsCachedQuery,
            $this->mockRoutesCachedQuery,
            new SpotFactory()
        );

        $this->office = OfficeFactory::make([
            'id' => TestValue::OFFICE_ID,
        ]);

        $this->coordinate = new Coordinate(
            TestValue::LATITUDE,
            TestValue::LONGITUDE
        );
    }

    /**
     * @test
     */
    public function it_returns_a_bunch_of_routes(): void
    {
        $responseLimit = 2;
        $distanceThreshold = 10;

        $pestRoutesRoutes = RouteData::getTestData(
            2,
            [
                'routeID' => self::BUCKET_ROUTE_ID,
                'title' => 'Bucket Routes',
            ],
            [
                'routeID' => self::REGULAR_ROUTE_ID,
                'title' => 'Regular Routes',
            ],
        );

        $pestRoutesSpots = SpotData::getTestData(
            4,
            [
                'spotID' => TestValue::SPOT_ID,
                'routeID' => self::BUCKET_ROUTE_ID,
            ],
            [
                'spotID' => TestValue::SPOT_ID + 1,
                'routeID' => self::BUCKET_ROUTE_ID,
            ],
            [
                'spotID' => TestValue::SPOT_ID + 2,
                'routeID' => self::REGULAR_ROUTE_ID,
                'blockReason' => '',
            ],
            [
                'spotID' => TestValue::SPOT_ID + 3,
                'routeID' => self::REGULAR_ROUTE_ID,
                'spotCapacity' => '0',
                'blockReason' => sprintf(
                    '\ARO {"from": [%f, %f], "to": [-97.3202, 30.6499], "skills": ["TX"], "time": [12, 16]}',
                    TestValue::LONGITUDE + 0.0002,
                    TestValue::LATITUDE + 0.0002
                ),
            ],
        );

        $this->setUpMockRouteCachedQueryExpectations($pestRoutesRoutes);
        $this->setUpMockSpotsCachedQueryExpectations($pestRoutesSpots);

        $result = ($this->action)(new FindAvailableSpotsDto(
            office: $this->office,
            coordinate: $this->coordinate,
            isInitial: false,
            responseLimit: $responseLimit,
            distanceThreshold: $distanceThreshold,
            startDate: Carbon::parse(self::START_DATE, $this->office->getTimeZone()),
            endDate: Carbon::parse(self::END_DATE, $this->office->getTimeZone())
        ));

        $this->assertEquals(3, $result->count());

        $this->assertEquals(TestValue::SPOT_ID, $result->get(0)->getId());
        $this->assertEquals(TestValue::SPOT_ID + 1, $result->get(1)->getId());
        $this->assertEquals(TestValue::SPOT_ID + 3, $result->get(2)->getId());

        $this->assertEquals(SpotType::BUCKET, $result->get(0)->getType());
        $this->assertEquals(SpotType::BUCKET, $result->get(1)->getType());
        $this->assertEquals(SpotType::ARO_BLOCKED, $result->get(2)->getType());
    }

    /**
     * @test
     */
    public function it_filters_aro_spots_by_distance(): void
    {
        $pestRoutesRoutes = RouteData::getTestData(
            1,
            [
                'routeID' => self::REGULAR_ROUTE_ID,
                'title' => 'Regular Routes',
            ],
        );

        $pestRoutesSpots = SpotData::getTestData(
            4,
            [
                'spotID' => TestValue::SPOT_ID,
                'routeID' => self::REGULAR_ROUTE_ID,
                'spotCapacity' => '0',
                'blockReason' => sprintf(
                    '\ARO {"from": [%f, %f], "to": [-97.3202, 30.6499], "skills": ["TX"], "time": [12, 16]}',
                    TestValue::LONGITUDE + 0.0002,
                    TestValue::LATITUDE + 0.0002
                ),
            ],
            [
                'spotID' => TestValue::SPOT_ID + 1,
                'routeID' => self::REGULAR_ROUTE_ID,
                'spotCapacity' => '0',
                'blockReason' => sprintf(
                    '\ARO {"from": [-97.3202, 30.6499], "to": [%f, %f], "skills": ["TX"], "time": [12, 16]}',
                    TestValue::LONGITUDE + 0.0002,
                    TestValue::LATITUDE + 0.0002
                ),
            ],
            [
                'spotID' => TestValue::SPOT_ID + 2,
                'routeID' => self::REGULAR_ROUTE_ID,
                'spotCapacity' => '0',
                'blockReason' => sprintf(
                    '\ARO {"from": [%f, %f], "to": [%f, %f], "skills": ["TX"], "time": [12, 16]}',
                    TestValue::LONGITUDE + 0.1,
                    TestValue::LATITUDE + 0.1,
                    TestValue::LONGITUDE + 0.11,
                    TestValue::LATITUDE + 0.11,
                ),
            ],
            [
                'spotID' => TestValue::SPOT_ID + 3,
                'routeID' => self::REGULAR_ROUTE_ID,
                'spotCapacity' => '0',
                'blockReason' => sprintf(
                    '\ARO {"from": [], "to": [], "skills": ["TX"], "time": [12, 16]}',
                ),
            ],
        );

        $this->setUpMockRouteCachedQueryExpectations($pestRoutesRoutes);
        $this->setUpMockSpotsCachedQueryExpectations($pestRoutesSpots);

        $result = $this->executeAction();

        $this->assertEquals(2, $result->count());

        $this->assertEquals(TestValue::SPOT_ID, $result->get(0)->getId());
        $this->assertEquals(TestValue::SPOT_ID + 1, $result->get(1)->getId());
    }

    /**
     * @test
     */
    public function it_filters_aro_spots_by_skills(): void
    {
        $pestRoutesRoutes = RouteData::getTestData(
            1,
            [
                'routeID' => self::REGULAR_ROUTE_ID,
                'title' => 'Regular Routes',
            ],
        );

        $pestRoutesSpots = SpotData::getTestData(
            3,
            [
                'spotID' => TestValue::SPOT_ID,
                'routeID' => self::REGULAR_ROUTE_ID,
                'spotCapacity' => '0',
                'blockReason' => sprintf(
                    '\ARO {"from": [%f, %f], "to": [-97.3202, 30.6499], "skills": ["TX", "INI"], "time": [12, 16]}',
                    TestValue::LONGITUDE + 0.0002,
                    TestValue::LATITUDE + 0.0002
                ),
            ],
            [
                'spotID' => TestValue::SPOT_ID + 1,
                'routeID' => self::REGULAR_ROUTE_ID,
                'spotCapacity' => '0',
                'blockReason' => sprintf(
                    '\ARO {"from": [-97.3202, 30.6499], "to": [%f, %f], "skills": ["TX"], "time": [12, 16]}',
                    TestValue::LONGITUDE + 0.0002,
                    TestValue::LATITUDE + 0.0002
                ),
            ],
            [
                'spotID' => TestValue::SPOT_ID + 3,
                'routeID' => self::REGULAR_ROUTE_ID,
                'spotCapacity' => '0',
                'blockReason' => sprintf(
                    '\ARO {"from": [-97.3202, 30.6499], "to": [%f, %f], "skills": [], "time": [12, 16]}',
                    TestValue::LONGITUDE + 0.0002,
                    TestValue::LATITUDE + 0.0002
                ),
            ],
        );

        $this->setUpMockRouteCachedQueryExpectations($pestRoutesRoutes);
        $this->setUpMockSpotsCachedQueryExpectations($pestRoutesSpots);

        $result = $this->executeAction(isInitial: true);

        $this->assertEquals(1, $result->count());

        $this->assertEquals(TestValue::SPOT_ID, $result->get(0)->getId());
    }

    /**
     * @test
     */
    public function it_limits_and_sorts_output(): void
    {
        $limit = 2;

        $pestRoutesRoutes = RouteData::getTestData(
            1,
            [
                'routeID' => self::BUCKET_ROUTE_ID,
                'title' => 'Bucket Routes',
            ],
        );

        $pestRoutesSpots = SpotData::getTestData(
            6,
            [
                'spotID' => 1,
                'routeID' => self::BUCKET_ROUTE_ID,
            ],
            [
                'spotID' => 2,
                'routeID' => self::BUCKET_ROUTE_ID,
            ],
            [
                'spotID' => 3,
                'routeID' => self::BUCKET_ROUTE_ID,
            ],
            [
                'spotID' => 4,
                'routeID' => self::BUCKET_ROUTE_ID,
            ],
            [
                'spotID' => 5,
                'routeID' => self::BUCKET_ROUTE_ID,
            ],
            [
                'spotID' => 6,
                'routeID' => self::BUCKET_ROUTE_ID,
            ],
        );

        $this->setUpMockRouteCachedQueryExpectations($pestRoutesRoutes);
        $this->setUpMockSpotsCachedQueryExpectations($pestRoutesSpots);

        $result = $this->executeAction($limit);

        $this->assertEquals(4, $result->count());

        $this->assertEquals('AM', $result->get(0)->getWindow());
        $this->assertEquals('AM', $result->get(1)->getWindow());
        $this->assertEquals('PM', $result->get(2)->getWindow());
        $this->assertEquals('PM', $result->get(3)->getWindow());
    }

    /**
     * @test
     */
    public function it_doesnt_return_occupied_spots(): void
    {
        $pestRoutesRoutes = RouteData::getTestData(
            1,
            [
                'routeID' => self::BUCKET_ROUTE_ID,
                'title' => 'Bucket Routes',
            ],
        );

        $pestRoutesSpots = SpotData::getTestData(
            2,
            [
                'spotID' => TestValue::SPOT_ID,
                'routeID' => self::BUCKET_ROUTE_ID,
            ],
            [
                'spotID' => TestValue::SPOT_ID + 1,
                'currentAppointment' => '2345242353',
                'routeID' => self::BUCKET_ROUTE_ID,
            ],
        );

        $this->setUpMockRouteCachedQueryExpectations($pestRoutesRoutes);
        $this->setUpMockSpotsCachedQueryExpectations($pestRoutesSpots);

        $result = $this->executeAction();

        $this->assertEquals(1, $result->count());
        $this->assertEquals(TestValue::SPOT_ID, $result->get(0)->getId());
    }

    private function executeAction(int|null $limit = null, bool $isInitial = false): Collection
    {
        return ($this->action)(new FindAvailableSpotsDto(
            office: $this->office,
            coordinate: $this->coordinate,
            isInitial: $isInitial,
            responseLimit: $limit,
            distanceThreshold: self::DISTANCE_THRESHOLD,
            startDate: Carbon::parse(self::START_DATE, $this->office->getTimeZone()),
            endDate: Carbon::parse(self::END_DATE, $this->office->getTimeZone())
        ));
    }

    private function setUpMockRouteCachedQueryExpectations(Collection $pestRoutesRoutes): void
    {
        $this->mockRoutesCachedQuery
            ->shouldReceive('cached')
            ->once()
            ->andReturnSelf();
        $this->mockRoutesCachedQuery
            ->shouldReceive('get')
            ->once()
            ->andReturn($pestRoutesRoutes);
    }

    private function setUpMockSpotsCachedQueryExpectations(Collection $pestRoutesSpots): void
    {
        $this->mockSpotsCachedQuery
            ->shouldReceive('cached')
            ->once()
            ->andReturnSelf();
        $this->mockSpotsCachedQuery
            ->shouldReceive('get')
            ->once()
            ->andReturn($pestRoutesSpots);
    }
}
