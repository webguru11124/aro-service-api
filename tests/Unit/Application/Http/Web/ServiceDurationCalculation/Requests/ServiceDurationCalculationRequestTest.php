<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Web\ServiceDurationCalculation\Requests;

use App\Application\Http\Web\ServiceDurationCalculation\Requests\ServiceDurationCalculationRequest;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;

class ServiceDurationCalculationRequestTest extends AbstractRequestTest
{
    public function getTestedRequest(): Request
    {
        return new ServiceDurationCalculationRequest();
    }

    public static function getInvalidData(): array
    {
        return [
            'both_fields_cannot_be_missing' => [
                [],
            ],
            'linearFootPerSecond_must_be_numeric' => [
                [
                    'calculateServiceDuration' => '1',
                    'linearFootPerSecond' => 'non-numeric',
                    'squareFootageOfHouse' => 2000,
                    'squareFootageOfLot' => 3000,
                ],
            ],
            'squareFootageOfHouse_must_be_numeric_and_minimum_1' => [
                [
                    'calculateServiceDuration' => '1',
                    'squareFootageOfHouse' => 0,
                    'squareFootageOfLot' => 3000,
                ],
            ],
            'squareFootageOfLot_must_be_numeric_and_minimum_1' => [
                [
                    'calculateServiceDuration' => '1',
                    'squareFootageOfHouse' => 2000,
                    'squareFootageOfLot' => -1,
                ],
            ],
            'actualDuration_required_if_calculateLf_present_and_must_be_numeric_and_minimum_1' => [
                [
                    'calculateLf' => '1',
                    'squareFootageOfHouse' => 2000,
                    'squareFootageOfLot' => 3000,
                    'actualDuration' => 0,
                ],
            ],
        ];
    }

    public static function getValidData(): array
    {
        return [
            'valid_data_for_service_duration_calculation' => [
                [
                    'calculateServiceDuration' => '1',
                    'linearFootPerSecond' => '1.5',
                    'squareFootageOfHouse' => 2000,
                    'squareFootageOfLot' => 3000,
                ],
            ],
            'valid_data_for_lf_calculation' => [
                [
                    'calculateLf' => '1',
                    'squareFootageOfHouse' => 2500,
                    'squareFootageOfLot' => 3500,
                    'actualDuration' => 120,
                ],
            ],
        ];
    }
}
