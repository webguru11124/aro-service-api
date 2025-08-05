<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes\DataProcessors;

use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\OfficesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesSpotsDataProcessor;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Resources\Spots\Params\SearchSpotsParams;
use Aptive\PestRoutesSDK\Resources\Spots\Spot;
use Aptive\PestRoutesSDK\Resources\Spots\SpotsResource;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\OfficeData;
use Tests\Tools\PestRoutesData\SpotData;
use Tests\Tools\TestValue;
use Tests\Traits\PestRoutesClientMockBuilderAware;

class PestRoutesSpotsDataProcessorTest extends TestCase
{
    use PestRoutesClientMockBuilderAware;

    private OfficesDataProcessor|MockInterface $officesDataProcessorMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->officesDataProcessorMock = \Mockery::mock(OfficesDataProcessor::class);
    }

    private const BLOCK_REASON = 'Blocked by ARO';

    /**
     * @dataProvider spotsDataProvider
     *
     * @param Collection $spots
     * @param int[] $expectedIds
     *
     * @return void
     *
     * @test
     */
    public function it_extracts_spots(Collection $spots, array $expectedIds): void
    {
        $searchSpotsParam = new SearchSpotsParams(
            officeIds: [TestValue::OFFICE_ID],
            routeIds: [TestValue::ROUTE_ID]
        );

        $offices = OfficeData::getTestData(1, ['officeID' => TestValue::OFFICE_ID]);
        $officesCollection = new PestRoutesCollection($offices->all());

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(SpotsResource::class)
            ->callSequence('spots', 'includeData', 'search', 'all')
            ->methodExpectsArgs('search', function (
                SearchSpotsParams $params,
                PestRoutesCollection $offices
            ) use ($searchSpotsParam, $officesCollection) {
                return $params === $searchSpotsParam
                    && $offices->getItems() === $officesCollection->getItems();
            })
            ->willReturn(new PestRoutesCollection($spots->all()))
            ->mock();

        $this->officesDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->andReturn($offices);

        $subject = new PestRoutesSpotsDataProcessor($pestRoutesClientMock, $this->officesDataProcessorMock);

        $result = $subject->extract(TestValue::OFFICE_ID, $searchSpotsParam);

        $this->assertEquals($expectedIds, $result->map(fn (Spot $spot) => $spot->id)->values()->toArray());
    }

    /**
     * @test
     */
    public function it_extracts_spot_ids(): void
    {
        $searchSpotsParam = new SearchSpotsParams(
            officeIds: [TestValue::OFFICE_ID],
            routeIds: [TestValue::ROUTE_ID]
        );

        $expectedIds = [
            $this->faker->randomNumber(4),
            $this->faker->randomNumber(4),
        ];

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(SpotsResource::class)
            ->callSequence('spots', 'search', 'ids')
            ->methodExpectsArgs('search', function (
                SearchSpotsParams $params,
            ) use ($searchSpotsParam) {
                return $params === $searchSpotsParam;
            })
            ->willReturn($expectedIds)
            ->mock();

        $subject = new PestRoutesSpotsDataProcessor($pestRoutesClientMock, $this->officesDataProcessorMock);

        $result = $subject->extractIds(TestValue::OFFICE_ID, $searchSpotsParam);

        $this->assertEquals($expectedIds, $result->toArray());
    }

    /**
    * @test
    */
    public function it_blocks_single_spot(): void
    {
        $client = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(SpotsResource::class)
            ->callSequence('spots', 'block')
            ->methodExpectsArgs('block', [TestValue::SPOT_ID, [], self::BLOCK_REASON])
            ->willReturn([TestValue::SPOT_ID])
            ->mock();

        $subject = new PestRoutesSpotsDataProcessor($client, $this->officesDataProcessorMock);

        $subject->block(TestValue::OFFICE_ID, TestValue::SPOT_ID, self::BLOCK_REASON);
    }

    /**
    * @test
    */
    public function it_unblocks_single_spot(): void
    {
        $client = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(SpotsResource::class)
            ->callSequence('spots', 'unblock')
            ->methodExpectsArgs('unblock', [TestValue::SPOT_ID])
            ->willReturn([TestValue::SPOT_ID])
            ->mock();

        $subject = new PestRoutesSpotsDataProcessor($client, $this->officesDataProcessorMock);

        $subject->unblock(TestValue::OFFICE_ID, TestValue::SPOT_ID);
    }

    /**
     * @test
     */
    public function it_blocks_multiple_spot(): void
    {
        $spotIds = [
            random_int(100, 200),
            random_int(201, 300),
        ];

        $spotsCollection = SpotData::getTestData(
            2,
            ['spotID' => $spotIds[0]],
            ['spotID' => $spotIds[1]]
        );

        $client = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(SpotsResource::class)
            ->callSequence('spots', 'block')
            ->methodExpectsArgs('block', [null, $spotIds, self::BLOCK_REASON])
            ->willReturn($spotIds)
            ->mock();

        $subject = new PestRoutesSpotsDataProcessor($client, $this->officesDataProcessorMock);

        $subject->blockMultiple(TestValue::OFFICE_ID, $spotsCollection, self::BLOCK_REASON);
    }

    /**
     * @test
     */
    public function it_handles_block_multiple_for_empty_spots_collection(): void
    {
        $client = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(SpotsResource::class)
            ->callSequence('spots', 'block')
            ->times(0)
            ->mock();

        $subject = new PestRoutesSpotsDataProcessor($client, $this->officesDataProcessorMock);

        $subject->blockMultiple(TestValue::OFFICE_ID, new Collection(), self::BLOCK_REASON);
    }

    /**
     * @test
     */
    public function it_unblocks_multiple_spot(): void
    {
        $spotIds = [
            random_int(100, 200),
            random_int(201, 300),
        ];

        $spotsCollection = SpotData::getTestData(
            2,
            ['spotID' => $spotIds[0]],
            ['spotID' => $spotIds[1]]
        );

        $client = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(SpotsResource::class)
            ->callSequence('spots', 'unblock')
            ->methodExpectsArgs('unblock', [null, $spotIds])
            ->willReturn($spotIds)
            ->mock();

        $subject = new PestRoutesSpotsDataProcessor($client, $this->officesDataProcessorMock);

        $subject->unblockMultiple(TestValue::OFFICE_ID, $spotsCollection);
    }

    /**
     * @test
    */
    public function it_handles_unblock_multiple_for_empty_spots_collection(): void
    {
        $client = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(SpotsResource::class)
            ->callSequence('spots', 'unblock')
            ->times(0)
            ->mock();

        $subject = new PestRoutesSpotsDataProcessor($client, $this->officesDataProcessorMock);

        $subject->unblockMultiple(TestValue::OFFICE_ID, new Collection());
    }

    public static function spotsDataProvider(): iterable
    {
        yield [
            new Collection([
                SpotData::getTestData(1, ['spotID' => 1])->first(),
                SpotData::getTestData(1, ['spotID' => 2])->first(),
                SpotData::getTestData(
                    1,
                    [
                        'spotID' => 3,
                        'blockReason' => 'Blocked by aro',
                    ]
                )->first(),
            ]),
            [1, 2, 3],
        ];

        yield [
            new Collection([
                SpotData::getTestData(1, ['spotID' => 1])->first(),
                SpotData::getTestData(1, ['spotID' => 2])->first(),
                SpotData::getTestData(
                    1,
                    [
                        'spotID' => 3,
                        'blockReason' => 'Blocked by some reason',
                    ]
                )->first(),
            ]),
            [1, 2, 3],
        ];
    }

    /**
     * @dataProvider spotsDataProvider
     *
     * @param Collection $spots
     * @param int[] $expectedIds
     *
     * @return void
     *
     * @test
     */
    public function it_extracts_spots_asynchronously(Collection $spots, array $expectedIds): void
    {
        $searchSpotsParam = new SearchSpotsParams(
            officeIds: [TestValue::OFFICE_ID],
            routeIds: [TestValue::ROUTE_ID]
        );

        $offices = OfficeData::getTestData(1, ['officeID' => TestValue::OFFICE_ID]);
        $officesCollection = new PestRoutesCollection($offices->all());

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(SpotsResource::class)
            ->callSequence('spots', 'searchAsync')
            ->methodExpectsArgs('search', function (
                SearchSpotsParams $params,
                PestRoutesCollection $offices
            ) use ($searchSpotsParam, $officesCollection) {
                return $params === $searchSpotsParam
                    && $offices->getItems() === $officesCollection->getItems();
            })
            ->willReturn(new PestRoutesCollection($spots->all()))
            ->mock();

        $this->officesDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->andReturn($offices);

        $subject = new PestRoutesSpotsDataProcessor($pestRoutesClientMock, $this->officesDataProcessorMock);

        $result = $subject->extractAsync(TestValue::OFFICE_ID, $searchSpotsParam);

        $this->assertEquals($expectedIds, $result->map(fn (Spot $spot) => $spot->id)->values()->toArray());
    }
}
