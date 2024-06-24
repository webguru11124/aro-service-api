<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Calendar\V1\Requests;

use App\Application\Http\Api\Calendar\V1\Requests\GetOfficeEmployeesRequest;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;
use Tests\Tools\TestValue;

class GetOfficeEmployeesRequestTest extends AbstractRequestTest
{
    public function getTestedRequest(): Request
    {
        return new GetOfficeEmployeesRequest();
    }

    public static function getInvalidData(): array
    {
        return [
            'office_id_must_be_an_integer' => [
                [
                    'office_id' => 'test',
                ],
            ],
            'office_id_must_greater_than_0' => [
                [
                    'office_id' => 0,
                ],
            ],
        ];
    }

    public static function getValidData(): array
    {
        return [
            'valid_request_data' => [
                [
                    'office_id' => TestValue::OFFICE_ID,
                ],
            ],
        ];
    }
}
