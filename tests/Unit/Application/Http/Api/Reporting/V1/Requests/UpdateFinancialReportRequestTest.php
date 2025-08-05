<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Reporting\V1\Requests;

use App\Application\Http\Api\Reporting\V1\Requests\UpdateFinancialReportRequest;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;

class UpdateFinancialReportRequestTest extends AbstractRequestTest
{
    public function getTestedRequest(): Request
    {
        return new UpdateFinancialReportRequest();
    }

    public static function getInvalidData(): array
    {
        return [
            'year_must_be_an_integer' => [
                [
                    'year' => 'a',
                ],
            ],
            'year_must_be_greater_than_2020' => [
                [
                    'year' => 2019,
                ],
            ],
            'year_must_be_lower_than_2099' => [
                [
                    'year' => 2100,
                ],
            ],
            'month_must_be_a_string' => [
                [
                    'month' => 1,
                ],
            ],
            'month_must_be_a_valid_short_month_name' => [
                [
                    'month' => 'January',
                ],
            ],
        ];
    }

    public static function getValidData(): array
    {
        return [
            'valid_request_data_with_all_parameters' => [
                [
                    'year' => 2023,
                    'month' => 'Mar',
                ],
            ],
        ];
    }
}
