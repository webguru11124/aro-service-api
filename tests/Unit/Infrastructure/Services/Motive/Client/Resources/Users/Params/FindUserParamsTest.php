<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Motive\Client\Resources\Users\Params;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractHttpParams;
use App\Infrastructure\Services\Motive\Client\Resources\Users\Params\FindUserParams;
use Tests\TestCase;

class FindUserParamsTest extends TestCase
{
    private FindUserParams $params;

    private function getParams(): AbstractHttpParams
    {
        return new FindUserParams(
            email: $this->faker->email(),
            username: $this->faker->userName(),
            driverCompanyId: $this->faker->text(7),
            phone: $this->faker->phoneNumber()
        );
    }

    /**
     * @test
     */
    public function it_transforms_params_to_array_correctly(): void
    {
        $this->params = $this->getParams();
        $result = $this->params->toArray();

        $this->assertEquals($this->params->email, $result['email']);
        $this->assertEquals($this->params->username, $result['username']);
        $this->assertEquals($this->params->driverCompanyId, $result['driver_company_id']);
        $this->assertEquals($this->params->phone, $result['phone']);
    }
}
