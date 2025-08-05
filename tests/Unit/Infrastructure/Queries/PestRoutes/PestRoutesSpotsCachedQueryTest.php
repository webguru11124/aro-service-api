<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Queries\PestRoutes;

use App\Infrastructure\Queries\PestRoutes\Params\SpotsCachedQueryParams;
use App\Infrastructure\Queries\PestRoutes\PestRoutesSpotsCachedQuery;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesOfficesDataProcessor;
use Aptive\PestRoutesSDK\Client as PestRoutesClient;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Converters\DateTimeConverter;
use Aptive\PestRoutesSDK\Resources\Routes\Params\SearchRoutesParams;
use Aptive\PestRoutesSDK\Resources\Spots\SpotsResource;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Psr\SimpleCache\CacheInterface;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\OfficeData;
use Tests\Tools\PestRoutesData\SpotData;
use Tests\Tools\TestValue;
use Tests\Traits\PestRoutesClientMockBuilderAware;

class PestRoutesSpotsCachedQueryTest extends TestCase
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
    public function it_gets_spots_from_source_when_cache_disabled(): void
    {
        $spots = new PestRoutesCollection(SpotData::getTestData(5)->all());
        $clientMock = $this->setUpPestRoutesMockClientExpectations($spots);

        $this->setMockOfficeDataProcessorExpectations();

        $this->mockCache->shouldReceive('put')->never();

        $query = new PestRoutesSpotsCachedQuery(
            $clientMock,
            $this->mockOfficeDataProcessor,
            $this->mockCache
        );

        $result = $query
            ->get(new SpotsCachedQueryParams(
                TestValue::OFFICE_ID,
                Carbon::parse(self::START_DATE, DateTimeConverter::PEST_ROUTES_TIMEZONE),
                Carbon::parse(self::END_DATE, DateTimeConverter::PEST_ROUTES_TIMEZONE),
            ));

        $this->assertEquals($spots->items, $result->all());
    }

    /**
     * @test
     */
    public function it_gets_spots_from_source_and_populates_cache(): void
    {
        $spots = new PestRoutesCollection(SpotData::getTestData(5)->all());
        $clientMock = $this->setUpPestRoutesMockClientExpectations($spots);

        $this->setMockOfficeDataProcessorExpectations();

        $this->mockCache->shouldReceive('has')
            ->once()
            ->andReturnFalse();

        $this->mockCache->shouldReceive('set')
            ->times(10);

        $query = new PestRoutesSpotsCachedQuery(
            $clientMock,
            $this->mockOfficeDataProcessor,
            $this->mockCache
        );

        $result = $query
            ->cached(self::TTL)
            ->get(new SpotsCachedQueryParams(
                TestValue::OFFICE_ID,
                Carbon::parse(self::START_DATE, DateTimeConverter::PEST_ROUTES_TIMEZONE),
                Carbon::parse(self::END_DATE, DateTimeConverter::PEST_ROUTES_TIMEZONE),
            ));

        $this->assertEquals($spots->items, $result->all());
    }

    /**
     * @test
     */
    public function it_gets_spots_from_cache(): void
    {
        $this->setMockOfficeDataProcessorExpectations();

        $this->mockCache->shouldReceive('has')
            ->once()
            ->andReturnTrue();

        $this->mockCache->shouldReceive('get')
            ->times(10)
            ->andReturn(SpotData::getTestData(2));

        $mockClient = Mockery::mock(PestRoutesClient::class);
        $mockClient->shouldReceive('office')
            ->never();

        $query = new PestRoutesSpotsCachedQuery(
            $mockClient,
            $this->mockOfficeDataProcessor,
            $this->mockCache
        );

        $result = $query
            ->cached(self::TTL)
            ->get(new SpotsCachedQueryParams(
                TestValue::OFFICE_ID,
                Carbon::parse(self::START_DATE, DateTimeConverter::PEST_ROUTES_TIMEZONE),
                Carbon::parse(self::END_DATE, DateTimeConverter::PEST_ROUTES_TIMEZONE),
            ));

        $this->assertEquals(20, $result->count());
    }

    /**
     * @test
     */
    public function it_gets_spots_from_source_when_forced_to_bypass_cache(): void
    {
        $spots = new PestRoutesCollection(SpotData::getTestData(5)->all());
        $clientMock = $this->setUpPestRoutesMockClientExpectations($spots);

        $this->setMockOfficeDataProcessorExpectations();

        $this->mockCache->shouldReceive('has')
            ->never();

        $this->mockCache->shouldReceive('set')
            ->times(10);

        $query = new PestRoutesSpotsCachedQuery(
            $clientMock,
            $this->mockOfficeDataProcessor,
            $this->mockCache
        );

        $result = $query
            ->cached(self::TTL, true)
            ->get(new SpotsCachedQueryParams(
                TestValue::OFFICE_ID,
                Carbon::parse(self::START_DATE, DateTimeConverter::PEST_ROUTES_TIMEZONE),
                Carbon::parse(self::END_DATE, DateTimeConverter::PEST_ROUTES_TIMEZONE),
            ));

        $this->assertEquals($spots->items, $result->all());
    }

    private function setUpPestRoutesMockClientExpectations(PestRoutesCollection $routes): PestRoutesClient|MockInterface
    {
        return $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(SpotsResource::class)
            ->callSequence('spots', 'searchAsync')
            ->methodExpectsArgs('search', function (SearchRoutesParams $routesParams) {
                $params = $routesParams->toArray();

                return $params['officeIDs'] == [TestValue::OFFICE_ID]
                    && stripos((string) $params['date'], self::START_DATE) !== false
                    && stripos((string) $params['date'], self::END_DATE) !== false;
            })
            ->willReturn($routes)
            ->mock();
    }

    private function setMockOfficeDataProcessorExpectations(): void
    {
        $office = OfficeData::getTestData();
        $this->mockOfficeDataProcessor
            ->shouldReceive('extract')
            ->once()
            ->andReturn($office);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->mockCache);
        unset($this->mockOfficeDataProcessor);
    }
}
