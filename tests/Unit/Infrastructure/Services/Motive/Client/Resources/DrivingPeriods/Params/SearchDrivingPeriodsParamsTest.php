<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods\Params;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractHttpParams;
use App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods\DrivingPeriodStatus;
use App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods\DrivingPeriodType;
use App\Infrastructure\Services\Motive\Client\Resources\DrivingPeriods\Params\SearchDrivingPeriodsParams;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Unit\Infrastructure\Services\Motive\Client\Resources\HttpParamsTestUtils;

class SearchDrivingPeriodsParamsTest extends TestCase
{
    use HttpParamsTestUtils;

    private function getParams(): AbstractHttpParams
    {
        return new SearchDrivingPeriodsParams(
            startDate: Carbon::yesterday(),
            endDate: Carbon::yesterday(),
            driverIds: [$this->faker->randomNumber(5)],
            vehicleId: $this->faker->randomNumber(5),
            type: DrivingPeriodType::DRIVING,
            status: DrivingPeriodStatus::COMPLETE
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
        $this->assertEquals($this->params->vehicleId, $result['vehicle_ids']);
        $this->assertEquals($this->params->type->value, $result['type']);
        $this->assertEquals($this->params->status->value, $result['status']);
        $this->assertEquals(1, $result['page_no']);
        $this->assertEquals(100, $result['per_page']);
    }
}
