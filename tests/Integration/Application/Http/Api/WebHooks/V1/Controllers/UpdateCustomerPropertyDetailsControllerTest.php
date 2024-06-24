<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\WebHooks\V1\Controllers;

use App\Application\Http\Api\WebHooks\V1\Controllers\UpdateCustomerPropertyDetailsController;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @coversDefaultClass UpdateCustomerPropertyDetailsController
 */
class UpdateCustomerPropertyDetailsControllerTest extends TestCase
{
    use DatabaseTransactions;

    private const ROUTE_UPDATE_PROPERTY_DETAILS = '/api/v1/webhooks/customer-property-details';

    /**
     * @test
     */
    public function it_updates_property_details_and_returns_success_response(): void
    {
        $requestData = [
            'customer_id' => 1,
            'land_sqft' => 500,
            'building_sqft' => 300,
            'living_sqft' => 250,
        ];

        $response = $this->putJson(self::ROUTE_UPDATE_PROPERTY_DETAILS, $requestData);

        $response->assertOk();
        $response->assertJson([
            '_metadata' => [
                'success' => true,
            ],
            'result' => [
                'message' => 'Webhook triggered successfully.',
            ],
        ]);
    }

    /**
     * @test
     */
    public function it_returns_400_when_invalid_parameters_passed(): void
    {
        $response = $this->putJson(self::ROUTE_UPDATE_PROPERTY_DETAILS, []);

        $response->assertBadRequest();
    }
}
