<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Scheduling\V1\Requests;

use App\Application\Http\Api\Scheduling\V1\Requests\ScheduleAppointmentsRequest;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;

class ScheduleAppointmentsRequestTest extends AbstractRequestTest
{
    public function getTestedRequest(): Request
    {
        return new ScheduleAppointmentsRequest();
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
            'num_days_to_schedule_must_be_int' => [
                [
                    'office_ids' => [1],
                    'num_days_to_schedule' => 'a',
                ],
            ],
            'num_days_to_schedule_must_be_greater_than_zero' => [
                [
                    'office_ids' => [1],
                    'num_days_to_schedule' => 0,
                ],
            ],
            'num_days_to_schedule_must_be_less_than_15' => [
                [
                    'office_ids' => [1],
                    'num_days_to_schedule' => 15,
                ],
            ],
            'run_subsequent_optimization_must_be_boolean' => [
                [
                    'office_ids' => [1],
                    'run_subsequent_optimization' => 'a',
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
                    'num_days_to_schedule' => 14,
                    'run_subsequent_optimization' => true,
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
