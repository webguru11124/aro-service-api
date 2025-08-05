<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Calendar\V1\Responses;

use App\Application\Http\Api\Calendar\V1\Responses\GetOfficeEmployeesResponse;
use App\Domain\Calendar\Entities\Employee;
use Tests\TestCase;
use Tests\Tools\TestValue;
use Tests\Traits\AssertArrayHasAllKeys;

class GetOfficeEmployeesResponseTest extends TestCase
{
    use AssertArrayHasAllKeys;

    /**
     * @test
     */
    public function it_creates_correct_response(): void
    {
        $employeeCollection = collect([new Employee(1, 'John Doe', TestValue::WORKDAY_ID)]);
        $response = new GetOfficeEmployeesResponse($employeeCollection);

        $responseData = $response->getData(true);

        $this->assertArrayHasAllKeys([
            'result' => [
                'employees' => [
                    [
                        'id',
                        'name',
                        'external_id',
                    ],
                ],
            ],
        ], $responseData);
    }
}
