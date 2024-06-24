<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Tracking\V1\Requests;

use App\Application\Http\Api\Tracking\V1\Requests\FleetRoutesRequest;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;

class FleetRoutesRequestTest extends AbstractRequestTest
{
    public function getTestedRequest(): Request
    {
        return new FleetRoutesRequest();
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
            'office_id_is_required' => [
                [
                ],
            ],
            'date_must_be_string' => [
                [
                    'date' => 1234,
                ],
            ],
            'date_must_be_in_valid_format' => [
                [
                    'date' => '10.10.2000',
                ],
            ],
        ];
    }

    public static function getValidData(): array
    {
        return [
            'valid_request_data' => [
                [
                    'office_id' => 82,
                    'date' => '2021-01-01',
                ],
            ],
        ];
    }
}
