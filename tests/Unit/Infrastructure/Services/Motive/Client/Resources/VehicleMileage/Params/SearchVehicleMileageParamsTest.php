<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Motive\Client\Resources\VehicleMileage\Params;

use App\Infrastructure\Services\Motive\Client\Resources\VehicleMileage\Params\SearchVehicleMileageParams;
use Carbon\Carbon;
use Tests\TestCase;
use App\Infrastructure\Services\Motive\Client\Resources\AbstractHttpParams;
use Tests\Unit\Infrastructure\Services\Motive\Client\Resources\HttpParamsTestUtils;

class SearchVehicleMileageParamsTest extends TestCase
{
    use HttpParamsTestUtils;

    private function getParams(): AbstractHttpParams
    {
        return new SearchVehicleMileageParams(
            startDate: Carbon::now(),
            endDate: Carbon::now(),
            vehicleIds: [$this->faker->randomNumber(), $this->faker->randomNumber()]
        );
    }

    /**
     * @test
     */
    public function it_transforms_params_to_array_correctly(): void
    {
        $result = $this->params->toArray();

        $this->assertEquals($this->params->startDate->toDateString(), $result['start_date']);
        $this->assertEquals($this->params->endDate->toDateString(), $result['end_date']);
        $this->assertEquals($this->params->vehicleIds, $result['vehicle_ids']);
    }
}
