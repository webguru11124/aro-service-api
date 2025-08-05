<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Web\OptimizationOverview\Requests;

use App\Application\Http\Web\OptimizationOverview\Requests\OptimizationDetailsRequest;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;

class OptimizationDetailsRequestTest extends AbstractRequestTest
{
    public function getTestedRequest(): Request
    {
        return new OptimizationDetailsRequest();
    }

    public static function getInvalidData(): array
    {
        return [
            'missing_required_fields' => [
                [],
            ],
            'invalid_state_id_type' => [
                [
                    'state_id' => 'invalid_string',
                ],
            ],
            'invalid_state_id_value' => [
                [
                    'state_id' => -1,
                ],
            ],
            'invalid_sim_state_id_type' => [
                [
                    'state_id' => 1,
                    'sim_state_id' => 'a',
                ],
            ],
            'invalid_sim_state_id_value' => [
                [
                    'state_id' => 1,
                    'sim_state_id' => -1,
                ],
            ],
        ];
    }

    public static function getValidData(): array
    {
        return [
            'valid_with_minimum_required_fields' => [
                [
                    'state_id' => 123,
                ],
            ],
            'valid_with_all_fields' => [
                [
                    'state_id' => 123,
                    'sim_state_id' => 124,
                ],
            ],
        ];
    }
}
