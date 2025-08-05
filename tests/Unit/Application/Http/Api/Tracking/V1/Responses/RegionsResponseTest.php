<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Tracking\V1\Responses;

use App\Application\Http\Api\Tracking\V1\Responses\RegionsResponse;
use Tests\TestCase;
use Tests\Tools\Factories\Tracking\StaticRegionFactory;
use Tests\Traits\AssertArrayHasAllKeys;

class RegionsResponseTest extends TestCase
{
    use AssertArrayHasAllKeys;

    /**
     * @test
     */
    public function it_creates_correct_response(): void
    {
        $regions = collect(StaticRegionFactory::many(2));

        $response = new RegionsResponse($regions);

        $responseData = $response->getData(true);
        $this->assertArrayHasAllKeys([
            'result' => [
                [
                    'id',
                    'name',
                    'boundary' => [
                        [
                            'lat',
                            'lng',
                        ],
                    ],
                    'offices' => [
                        [
                            'id',
                            'name',
                            'region',
                            'address',
                            'city',
                            'state',
                            'timezone',
                            'timezone_name',
                            'location',
                        ],
                    ],
                ],
            ],
        ], $responseData);
    }
}
