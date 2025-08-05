<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Scheduling\V1\Responses;

use App\Application\Http\Api\Scheduling\V1\Responses\AvailableSpotsResponse;
use Tests\TestCase;
use Tests\Tools\Factories\SpotFactory;
use Tests\Traits\AssertArrayHasAllKeys;

class AvailableSpotsResponseTest extends TestCase
{
    use AssertArrayHasAllKeys;

    /**
     * @test
     */
    public function it_creates_correct_response(): void
    {
        $count = 2;
        $spots = collect(SpotFactory::many($count));

        $response = new AvailableSpotsResponse($spots, request());

        $responseData = $response->getData(true);

        $this->assertCount($count, $responseData['result']['spots']);
        $this->assertArrayHasAllKeys([
            'result' => [
                'spots' => [[
                    'spot_id',
                    'date',
                    'window',
                    'is_aro_spot',
                ]],
            ],
        ], $responseData);
    }
}
