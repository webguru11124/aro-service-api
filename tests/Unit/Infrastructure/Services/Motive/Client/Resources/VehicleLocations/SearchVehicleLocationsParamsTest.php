<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Motive\Client\Resources\VehicleLocations;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractHttpParams;
use App\Infrastructure\Services\Motive\Client\Resources\VehicleLocations\SearchVehicleLocationsParams;
use App\Infrastructure\Services\Motive\Client\Resources\VehicleLocations\VehicleStatusType;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Unit\Infrastructure\Services\Motive\Client\Resources\HttpParamsTestUtils;

class SearchVehicleLocationsParamsTest extends TestCase
{
    use HttpParamsTestUtils;

    private function getParams(): AbstractHttpParams
    {
        return new SearchVehicleLocationsParams(
            date: Carbon::now(),
            vehicleIds: [$this->faker->randomNumber(), $this->faker->randomNumber()]
        );
    }

    /**
     * @test
     */
    public function it_transforms_params_to_array_correctly(): void
    {
        $result = $this->params->toArray();

        $this->assertEquals($this->params->vehicleIds, $result['vehicle_ids']);
        $this->assertEquals($this->params->vehicleStatusType, VehicleStatusType::ACTIVE);
    }
}
