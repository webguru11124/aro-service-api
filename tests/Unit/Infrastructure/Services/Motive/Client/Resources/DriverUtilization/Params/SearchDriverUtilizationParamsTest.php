<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Motive\Client\Resources\DriverUtilization\Params;

use Carbon\Carbon;
use Tests\TestCase;
use App\Infrastructure\Services\Motive\Client\Resources\AbstractHttpParams;
use App\Infrastructure\Services\Motive\Client\Resources\DriverUtilization\Params\SearchDriverUtilizationParams;
use Tests\Unit\Infrastructure\Services\Motive\Client\Resources\HttpParamsTestUtils;

class SearchDriverUtilizationParamsTest extends TestCase
{
    use HttpParamsTestUtils;

    private function getParams(): AbstractHttpParams
    {
        return new SearchDriverUtilizationParams(
            startDate: Carbon::now(),
            endDate: Carbon::now(),
            driverIds: [$this->faker->randomNumber(), $this->faker->randomNumber()]
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
        $this->assertEquals($this->params->driverIds, $result['driver_ids']);
    }
}
