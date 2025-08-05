<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Motive\Client\Resources\Users\Params;

use App\Infrastructure\Services\Motive\Client\Resources\Users\Params\SearchUsersParams;
use App\Infrastructure\Services\Motive\Client\Resources\Users\UserDutyStatus;
use App\Infrastructure\Services\Motive\Client\Resources\Users\UserRole;
use App\Infrastructure\Services\Motive\Client\Resources\Users\UserStatus;
use App\Infrastructure\Services\Motive\Client\Resources\AbstractHttpParams;
use Tests\TestCase;
use Tests\Unit\Infrastructure\Services\Motive\Client\Resources\HttpParamsTestUtils;

class SearchUsersParamsTest extends TestCase
{
    use HttpParamsTestUtils;

    private function getParams(): AbstractHttpParams
    {
        return new SearchUsersParams(
            role : UserRole::ADMIN,
            dutyStatus : UserDutyStatus::ON_DUTY,
            status : UserStatus::ACTIVE,
            name : 'Test Name',
            vehicleId : 1
        );
    }

    /**
     * @test
     */
    public function it_transforms_params_to_array_correctly(): void
    {
        $result = $this->params->toArray();

        $this->assertEquals($this->params->role->value, $result['role']);
        $this->assertEquals($this->params->dutyStatus->value, $result['duty_status']);
        $this->assertEquals($this->params->status->value, $result['status']);
        $this->assertEquals($this->params->name, $result['name']);
    }
}
