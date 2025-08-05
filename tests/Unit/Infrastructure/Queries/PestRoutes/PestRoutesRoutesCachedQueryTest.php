<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Queries\PestRoutes;

use App\Infrastructure\Queries\PestRoutes\Params\RoutesCachedQueryParams;
use App\Infrastructure\Queries\PestRoutes\PestRoutesRoutesCachedQuery;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesOfficesDataProcessor;
use Aptive\PestRoutesSDK\Client as PestRoutesClient;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Converters\DateTimeConverter;
use Aptive\PestRoutesSDK\Resources\Routes\Params\SearchRoutesParams;
use Aptive\PestRoutesSDK\Resources\Routes\RoutesResource;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Psr\SimpleCache\CacheInterface;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\RouteData;
use Tests\Tools\TestValue;
use Tests\Traits\PestRoutesClientMockBuilderAware;

class PestRoutesRoutesCachedQueryTest extends TestCase
{
    use PestRoutesClientMockBuilderAware;

    private const START_DATE = '2024-04-01';
    private const END_DATE = '2024-04-10';
    private const TTL = 300;

    private PestRoutesOfficesDataProcessor|MockInterface $mockOfficeDataProcessor;
    private CacheInterface|MockInterface $mockCache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockOfficeDataProcessor = Mockery::mock(PestRoutesOfficesDataProcessor::class);
        $this->mockCache = Mockery::mock(CacheInterface::class);
    }

    /**
     * @test
     */
    public function it_gets_routes_from_source_when_cache_disabled(): void
    {
        $routes = new PestRoutesCollection(RouteData::getTestData(5)->all());
        $clientMock = $this->setUpPestRoutesMockClientExpectations($routes);

        $this->mockCache->shouldReceive('put')->never();

        $query = new PestRoutesRoutesCachedQuery(
            $clientMock,
            $this->mockOfficeDataProcessor,
            $this->mockCache
        );

        $result = $query
            ->get(new RoutesCachedQueryParams(
                TestValue::OFFICE_ID,
                Carbon::parse(self::START_DATE, DateTimeConverter::PEST_ROUTES_TIMEZONE),
                Carbon::parse(self::END_DATE, DateTimeConverter::PEST_ROUTES_TIMEZONE),
            ));

        $this->assertEquals($routes->items, $result->all());
    }

    /**
     * @test
     */
    public function it_gets_routes_from_source_and_populates_cache(): void
    {
        $routes = new PestRoutesCollection(RouteData::getTestData(5)->all());
        $clientMock = $this->setUpPestRoutesMockClientExpectations($routes);

        $this->mockCache->shouldReceive('has')
            ->once()
            ->andReturnFalse();

        $this->mockCache->shouldReceive('set')
            ->times(10);

        $query = new PestRoutesRoutesCachedQuery(
            $clientMock,
            $this->mockOfficeDataProcessor,
            $this->mockCache
        );

        $result = $query
            ->cached(self::TTL)
            ->get(new RoutesCachedQueryParams(
                TestValue::OFFICE_ID,
                Carbon::parse(self::START_DATE, DateTimeConverter::PEST_ROUTES_TIMEZONE),
                Carbon::parse(self::END_DATE, DateTimeConverter::PEST_ROUTES_TIMEZONE),
            ));

        $this->assertEquals($routes->items, $result->all());
    }

    /**
     * @test
     */
    public function it_gets_routes_from_cache(): void
    {
        $this->mockCache->shouldReceive('has')
            ->once()
            ->andReturnTrue();

        $this->mockCache->shouldReceive('get')
            ->times(10)
            ->andReturn(RouteData::getTestData(1));

        $mockClient = Mockery::mock(PestRoutesClient::class);
        $mockClient->shouldReceive('office')
            ->never();

        $query = new PestRoutesRoutesCachedQuery(
            $mockClient,
            $this->mockOfficeDataProcessor,
            $this->mockCache
        );

        $result = $query
            ->cached(self::TTL)
            ->get(new RoutesCachedQueryParams(
                TestValue::OFFICE_ID,
                Carbon::parse(self::START_DATE, DateTimeConverter::PEST_ROUTES_TIMEZONE),
                Carbon::parse(self::END_DATE, DateTimeConverter::PEST_ROUTES_TIMEZONE),
            ));

        $this->assertEquals(10, $result->count());
    }

    /**
     * @test
     */
    public function it_gets_routes_from_source_when_forced_to_bypass_cache(): void
    {
        $routes = new PestRoutesCollection(RouteData::getTestData(5)->all());
        $clientMock = $this->setUpPestRoutesMockClientExpectations($routes);

        $this->mockCache->shouldReceive('has')
            ->never();

        $this->mockCache->shouldReceive('set')
            ->times(10);

        $query = new PestRoutesRoutesCachedQuery(
            $clientMock,
            $this->mockOfficeDataProcessor,
            $this->mockCache
        );

        $result = $query
            ->cached(self::TTL, true)
            ->get(new RoutesCachedQueryParams(
                TestValue::OFFICE_ID,
                Carbon::parse(self::START_DATE, DateTimeConverter::PEST_ROUTES_TIMEZONE),
                Carbon::parse(self::END_DATE, DateTimeConverter::PEST_ROUTES_TIMEZONE),
            ));

        $this->assertEquals($routes->items, $result->all());
    }

    private function setUpPestRoutesMockClientExpectations(PestRoutesCollection $routes): PestRoutesClient|MockInterface
    {
        return $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(RoutesResource::class)
            ->callSequence('routes', 'includeData', 'search', 'all')
            ->methodExpectsArgs('search', function (SearchRoutesParams $routesParams) {
                $params = $routesParams->toArray();

                return $params['officeIDs'] == [TestValue::OFFICE_ID]
                    && stripos((string) $params['date'], self::START_DATE) !== false
                    && stripos((string) $params['date'], self::END_DATE) !== false;
            })
            ->willReturn($routes)
            ->mock();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->mockCache);
        unset($this->mockOfficeDataProcessor);
    }
}
