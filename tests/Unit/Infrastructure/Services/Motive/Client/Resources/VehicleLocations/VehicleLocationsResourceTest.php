<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Motive\Client\Resources\VehicleLocations;

use App\Infrastructure\Services\Motive\Client\HttpClient\HttpClient;
use App\Infrastructure\Services\Motive\Client\Resources\VehicleLocations\SearchVehicleLocationsParams;
use App\Infrastructure\Services\Motive\Client\Resources\VehicleLocations\VehicleLocation;
use App\Infrastructure\Services\Motive\Client\Resources\VehicleLocations\VehicleLocationsResource;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\MotiveData\VehicleLocationsData;

class VehicleLocationsResourceTest extends TestCase
{
    private const ENDPOINT_SEARCH = 'https://api.keeptruckin.com/v2/vehicle_locations';

    private HttpClient|MockInterface $httpClientMock;
    private VehicleLocationsResource $resource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClientMock = \Mockery::mock(HttpClient::class);
        $this->resource = new VehicleLocationsResource($this->httpClientMock);
    }

    /**
     * @test
     */
    public function it_returns_vehicle_locations(): void
    {
        $date = Carbon::now();
        $vehicleIds = [1];
        $params = new SearchVehicleLocationsParams(
            date: $date,
            vehicleIds: $vehicleIds,
        );

        $numberOfLocations = count($vehicleIds);
        $rawData = VehicleLocationsData::getRawTestData();
        $expectedIds = $rawData->map(fn (array $datum) => $datum['vehicle']->id)->toArray();

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
                'vehicles' => $rawData->all(),
                'pagination' => (object) $pagination,
            ]);

        /** @var Collection<int, VehicleLocation> $result */
        $result = $this->resource->search($params);
        $actualIds = $result->pluck('vehicleId')->toArray();

        $this->assertEquals($numberOfLocations, $result->count());
        $this->assertEquals($expectedIds, $actualIds);
    }
}
