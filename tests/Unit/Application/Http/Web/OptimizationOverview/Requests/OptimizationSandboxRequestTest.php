<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Web\OptimizationOverview\Requests;

use App\Application\Http\Web\OptimizationOverview\Requests\OptimizationSandboxRequest;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;

class OptimizationSandboxRequestTest extends AbstractRequestTest
{
    public function getTestedRequest(): Request
    {
        return new OptimizationSandboxRequest();
    }

    public static function getInvalidData(): array
    {
        return [
            'invalid_date_format' => [
                [
                    'optimization_date' => '2022-13-45',
                    'office_id' => 1,
                ],
            ],
            'invalid_office_id_type' => [
                [
                    'optimization_date' => '2022-04-25',
                    'office_id' => 'string',
                ],
            ],
            'invalid_office_id_value' => [
                [
                    'optimization_date' => '2022-04-25',
                    'office_id' => -1,
                ],
            ],
        ];
    }

    public static function getValidData(): array
    {
        return [
            'valid_with_optimization_date' => [
                [
                    'optimization_date' => '2022-04-25',
                ],
            ],
            'valid_with_office_id' => [
                [
                    'office_id' => 1,
                ],
            ],
            'valid_with_both_fields' => [
                [
                    'optimization_date' => '2022-04-25',
                    'office_id' => 1,
                ],
            ],
        ];
    }
}
