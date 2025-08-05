<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Web\OptimizationOverview\Requests;

use App\Application\Http\Web\OptimizationOverview\Requests\OptimizationMapRequest;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;

class OptimizationMapRequestTest extends AbstractRequestTest
{
    public function getTestedRequest(): Request
    {
        return new OptimizationMapRequest();
    }

    public static function getInvalidData(): array
    {
        return [
            'missing_all_required_fields' => [
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
        ];
    }

    public static function getValidData(): array
    {
        return [
            'valid_with_state_id' => [
                [
                    'state_id' => 123,
                ],
            ],
        ];
    }
}
