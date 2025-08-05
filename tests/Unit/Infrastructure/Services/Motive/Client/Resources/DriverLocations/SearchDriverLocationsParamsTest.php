<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Motive\Client\Resources\DriverLocations;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractHttpParams;
use App\Infrastructure\Services\Motive\Client\Resources\DriverLocations\SearchDriverLocationsParams;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Unit\Infrastructure\Services\Motive\Client\Resources\HttpParamsTestUtils;

class SearchDriverLocationsParamsTest extends TestCase
{
    use HttpParamsTestUtils;

    private function getParams(): AbstractHttpParams
    {
        return new SearchDriverLocationsParams(
            date: Carbon::now(),
            driverIds: [$this->faker->randomNumber(), $this->faker->randomNumber()]
        );
    }

    /**
     * @test
     */
    public function it_transforms_params_to_array_correctly(): void
    {
        $result = $this->params->toArray();

        $this->assertEquals($this->params->driverIds, $result['driver_ids']);
    }
}
