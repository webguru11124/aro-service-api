<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Scheduling\V1\Requests;

use App\Application\Http\Api\Scheduling\V1\Requests\RouteCreationRequest;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;

class RouteCreationRequestTest extends AbstractRequestTest
{
    public function getTestedRequest(): Request
    {
        return new RouteCreationRequest();
    }

    public static function getInvalidData(): array
    {
        return [
            'office_ids_must_contain_values' => [
                [
                    'office_ids' => [],
                ],
            ],
            'office_id_must_be_an_integer' => [
                [
                    'office_ids' => ['a'],
                ],
            ],
            'office_id_must_be_greater_than_zero' => [
                [
                    'office_ids' => [0],
                ],
            ],
            'start_date_must_be_string' => [
                [
                    'office_ids' => [1],
                    'start_date' => 1234,
                ],
            ],
            'start_date_must_be_in_valid_format' => [
                [
                    'office_ids' => [1],
                    'start_date' => '10.10.2000',
                ],
            ],
            'num_days_after_start_date_must_be_int' => [
                [
                    'office_ids' => [1],
                    'num_days_after_start_date' => 'a',
                ],
            ],
            'num_days_after_start_date_must_be_greater_than_zero' => [
                [
                    'office_ids' => [1],
                    'num_days_after_start_date' => 0,
                ],
            ],
            'num_days_to_create_routes_must_be_int' => [
                [
                    'office_ids' => [1],
                    'num_days_to_create_routes' => 'a',
                ],
            ],
            'num_days_to_create_routes_must_be_greater_than_zero' => [
                [
                    'office_ids' => [1],
                    'num_days_to_create_routes' => 0,
                ],
            ],
        ];
    }

    public static function getValidData(): array
    {
        return [
            'valid_request_data_with_all_parameters' => [
                [
                    'office_ids' => [1, 2],
                    'start_date' => '2021-01-01',
                    'num_days_after_start_date' => 1,
                    'num_days_to_create_routes' => 14,
                ],
            ],
            'valid_request_data_with_required_parameters_only' => [
                [
                    'office_ids' => [1, 2],
                ],
            ],
        ];
    }
}
