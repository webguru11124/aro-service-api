<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\RouteOptimization\V1\Requests;

use App\Application\Http\Api\RouteOptimization\V1\Requests\OptimizeRoutesRequest;
use Tests\Tools\AbstractRequestTest;
use Tests\Tools\TestValue;

class OptimizeRoutesRequestTest extends AbstractRequestTest
{
    private const VALID_DATA = [
        'office_ids' => [TestValue::OFFICE_ID],
    ];

    public function getTestedRequest(): OptimizeRoutesRequest
    {
        return new OptimizeRoutesRequest();
    }

    public static function getInvalidData(): iterable
    {
        yield 'empty_request' => [[]];

        yield 'office_ids_must_contain_values' => [array_diff_key(self::VALID_DATA, [
            'office_ids' => [],
        ])];

        yield 'office_id_must_be_an_integer' => [array_diff_key(self::VALID_DATA, [
            'office_ids' => ['a'],
        ])];

        yield 'office_id_must_be_greater_than_zero' => [array_diff_key(self::VALID_DATA, [
            'office_ids' => [0],
        ])];

        yield 'start_date_must_be_string' => [array_merge(self::VALID_DATA, [
            'start_date' => 1234,
        ])];

        yield 'start_date_must_be_in_valid_format' => [array_merge(self::VALID_DATA, [
            'start_date' => '10.10.2000',
        ])];

        yield 'num_days_to_optimize_must_be_int' => [array_merge(self::VALID_DATA, [
            'num_days_to_optimize' => 'a',
        ])];

        yield 'num_days_to_optimize_must_be_greater_than_zero' => [array_merge(self::VALID_DATA, [
            'num_days_to_optimize' => 0,
        ])];

        yield 'num_days_to_optimize_must_be_less_than_eight' => [array_merge(self::VALID_DATA, [
            'num_days_to_optimize' => 8,
        ])];

        yield 'num_days_after_start_date_must_be_int' => [array_merge(self::VALID_DATA, [
            'num_days_after_start_date' => 'a',
        ])];

        yield 'num_days_after_start_date_must_be_greater_than_zero' => [array_merge(self::VALID_DATA, [
            'num_days_after_start_date' => 0,
        ])];

        yield 'last_optimization_run_must_be_boolean' => [array_merge(self::VALID_DATA, [
            'last_optimization_run' => 'a',
        ])];

        yield 'simulation_must_be_boolean' => [array_merge(self::VALID_DATA, [
            'simulation_run' => 'a',
        ])];

        yield 'build_planned_optimization_must_be_boolean' => [array_merge(self::VALID_DATA, [
            'build_planned_optimization' => 'a',
        ])];

        yield 'disabled_rules_must_be_array' => [array_merge(self::VALID_DATA, [
            'disabled_rules' => 'a',
        ])];

        yield 'disabled_rules_value_must_be_string' => [array_merge(self::VALID_DATA, [
            'disabled_rules' => [1],
        ])];
    }

    public static function getValidData(): array
    {
        return [
            'valid_request_data_minimum_required' => [
                self::VALID_DATA,
            ],
            'valid_request_data_with_all_fields' => [
                array_merge(self::VALID_DATA, [
                    'start_date' => '2023-07-23',
                    'num_days_after_start_date' => 1,
                    'num_days_to_optimize' => 1,
                    'last_optimization_run' => true,
                    'simulation_run' => false,
                    'build_planned_optimization' => false,
                    'disabled_rules' => ['rule1', 'rule2'],
                ]),
            ],
        ];
    }
}
