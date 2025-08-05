<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods;

use App\Infrastructure\Services\Motive\Client\HttpClient\HttpClient;
use App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods\DrivingPeriod;
use App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods\DrivingPeriodsResource;
use App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods\Params\SearchDrivingPeriodsParams;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\MotiveData\DrivingPeriodData;
use Tests\Unit\Infrastructure\Services\Motive\Client\Resources\ResourceCanBeCached;

class DrivingPeriodsResourceTest extends TestCase
{
    use ResourceCanBeCached;

    private const ENDPOINT_SEARCH = 'https://api.keeptruckin.com/v1/driving_periods';

    private HttpClient|MockInterface $httpClientMock;
    private DrivingPeriodsResource $resource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClientMock = \Mockery::mock(HttpClient::class);
        $this->resource = new DrivingPeriodsResource($this->httpClientMock);
    }

    /**
     * @test
     */
    public function it_searches_driving_periods(): void
    {
        $params = new SearchDrivingPeriodsParams(
            startDate: Carbon::yesterday(),
            endDate: Carbon::yesterday(),
            driverIds: [$this->faker->randomNumber(5)]
        );

        $numberOfPeriods = $this->faker->randomNumber(2);
        $rawData = DrivingPeriodData::getRawTestData($numberOfPeriods);
        $expectedIds = $rawData->map(fn (array $datum) => $datum['id'])->toArray();

        $rawData = $rawData->map(fn (array $datum) => (object) ['driving_period' => (object) $datum]);
        $pagination = [
            'per_page' => 100,
            'page_no' => 1,
            'total' => 1,
        ];

        $this->httpClientMock
            ->shouldReceive('get')
            ->withSomeOfArgs(self::ENDPOINT_SEARCH, $params)
            ->once()
            ->andReturn((object) [
                'driving_periods' => $rawData->all(),
                'pagination' => (object) $pagination,
            ]);

        /** @var Collection<int, DrivingPeriod> $result */
        $result = $this->resource->search($params);
        $actualIds = $result->pluck('id')->toArray();

        $this->assertEquals($numberOfPeriods, $result->count());
        $this->assertEquals($expectedIds, $actualIds);
    }

    /**
     * @test
     */
    public function search_method_returns_all_data_from_multiple_pages_when_pagination_not_set(): void
    {
        $params = new SearchDrivingPeriodsParams(
            startDate: Carbon::yesterday(),
            endDate: Carbon::yesterday(),
        );

        $perPage = 100;
        $totalPages = 2;

        $rawData = DrivingPeriodData::getRawTestData($perPage);
        $rawData = $rawData->map(fn (array $datum) => (object) ['driving_period' => (object) $datum]);
        $pagination = [
            'per_page' => $perPage,
            'page_no' => 1,
            'total' => $perPage * $totalPages,
        ];

        $this->httpClientMock
            ->shouldReceive('get')
            ->times($totalPages)
            ->andReturn((object) [
                'driving_periods' => $rawData->all(),
                'pagination' => (object) $pagination,
            ]);

        /** @var Collection<int, DrivingPeriod> $result */
        $result = $this->resource->search($params);

        $this->assertEquals($perPage * $totalPages, $result->count());
    }

    /**
     * @test
     */
    public function search_method_returns_data_from_single_page_when_pagination_is_set(): void
    {
        $params = new SearchDrivingPeriodsParams(
            startDate: Carbon::yesterday(),
            endDate: Carbon::yesterday(),
        );
        $params->setPage(1);

        $perPage = 100;
        $totalPages = 2;

        $rawData = DrivingPeriodData::getRawTestData($perPage);
        $rawData = $rawData->map(fn (array $datum) => (object) ['driving_period' => (object) $datum]);
        $pagination = [
            'per_page' => $perPage,
            'page_no' => 1,
            'total' => $perPage * $totalPages,
        ];

        $this->httpClientMock
            ->shouldReceive('get')
            ->once()
            ->andReturn((object) [
                'driving_periods' => $rawData->all(),
                'pagination' => (object) $pagination,
            ]);

        /** @var Collection<int, DrivingPeriod> $result */
        $result = $this->resource->search($params);

        $this->assertEquals($perPage, $result->count());
    }

    private function getTestedResourceClass(): string
    {
        return DrivingPeriodsResource::class;
    }
}
