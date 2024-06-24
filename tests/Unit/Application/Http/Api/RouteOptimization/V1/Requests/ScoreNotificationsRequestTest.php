<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\RouteOptimization\V1\Requests;

use App\Application\Http\Api\RouteOptimization\V1\Requests\ScoreNotificationsRequest;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;

class ScoreNotificationsRequestTest extends AbstractRequestTest
{
    public function getTestedRequest(): Request
    {
        return new ScoreNotificationsRequest();
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
            'date_must_be_string' => [
                [
                    'office_ids' => [1],
                    'date' => 1234,
                ],
            ],
            'date_must_be_in_valid_format' => [
                [
                    'office_ids' => [1],
                    'date' => '10.10.2000',
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
                    'date' => '2021-01-01',
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
