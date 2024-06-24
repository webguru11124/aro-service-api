<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\WebHooks\V1\Requests;

use App\Application\Http\Api\WebHooks\V1\Requests\UpdateCustomerPropertyDetailsRequest;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;

class UpdateCustomerPropertyDetailsRequestTest extends AbstractRequestTest
{
    public function getTestedRequest(): Request
    {
        return new UpdateCustomerPropertyDetailsRequest();
    }

    public static function getInvalidData(): array
    {
        return [
            'customer_id_must_be_an_integer' => [
                ['customer_id' => 'string', 'land_sqft' => 100, 'building_sqft' => 200, 'living_sqft' => 300],
            ],
            'land_sqft_must_be_numeric' => [
                ['customer_id' => 1, 'land_sqft' => 'string', 'building_sqft' => 200, 'living_sqft' => 300],
            ],
            'building_sqft_must_be_numeric' => [
                ['customer_id' => 1, 'land_sqft' => 100, 'building_sqft' => 'string', 'living_sqft' => 300],
            ],
            'living_sqft_must_be_numeric' => [
                ['customer_id' => 1, 'land_sqft' => 100, 'building_sqft' => 200, 'living_sqft' => 'string'],
            ],
            'all_fields_are_required' => [
                ['customer_id' => null, 'land_sqft' => null, 'building_sqft' => null, 'living_sqft' => null],
            ],
        ];
    }

    public static function getValidData(): array
    {
        return [
            'valid_request_data' => [
                ['customer_id' => 1, 'land_sqft' => 100.5, 'building_sqft' => 200, 'living_sqft' => 300.5],
            ],
        ];
    }
}
