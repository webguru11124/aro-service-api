<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Motive\Client\Resources\DriverLocations;

use App\Infrastructure\Services\Motive\Client\HttpClient\HttpClient;
use App\Infrastructure\Services\Motive\Client\Resources\DriverLocations\DriverLocation;
use App\Infrastructure\Services\Motive\Client\Resources\DriverLocations\DriverLocationsResource;
use App\Infrastructure\Services\Motive\Client\Resources\DriverLocations\SearchDriverLocationsParams;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\MotiveData\DriverLocationsData;

class DriverLocationsResourceTest extends TestCase
{
    private const ENDPOINT_SEARCH = 'https://api.keeptruckin.com/v1/driver_locations';

    private HttpClient|MockInterface $httpClientMock;
    private DriverLocationsResource $resource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClientMock = \Mockery::mock(HttpClient::class);
        $this->resource = new DriverLocationsResource($this->httpClientMock);
    }

    /**
     * @test
     */
    public function it_returns_driver_locations(): void
    {
        $date = Carbon::now();
        $driverIds = [1];
        $params = new SearchDriverLocationsParams(
            date: $date,
            driverIds: $driverIds,
        );

        $numberOfLocations = count($driverIds);
        $rawData = DriverLocationsData::getRawTestData();
        $expectedIds = $rawData->map(fn (array $datum) => $datum['user']->id)->toArray();

        $rawData = $rawData->map(fn (array $datum) => (object) $datum);
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
                'users' => $rawData->all(),
                'pagination' => (object) $pagination,
            ]);

        /** @var Collection<int, DriverLocation> $result */
        $result = $this->resource->search($params);
        $actualIds = $result->pluck('driverId')->toArray();

        $this->assertEquals($numberOfLocations, $result->count());
        $this->assertEquals($expectedIds, $actualIds);
    }

    private function getTestedResourceClass(): string
    {
        return DriverLocation::class;
    }
}
