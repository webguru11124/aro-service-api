<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Caching\V1\Responses;

use App\Application\Http\Api\Caching\Responses\RefreshAvailableSpotsCacheResponse;
use Aptive\Component\Http\HttpStatus;
use Tests\TestCase;
use Tests\Traits\AssertArrayHasAllKeys;

class RefreshAvailableSpotsCacheResponseTest extends TestCase
{
    use AssertArrayHasAllKeys;

    /**
     * @test
     */
    public function it_creates_correct_response(): void
    {
        $response = new RefreshAvailableSpotsCacheResponse();

        $responseData = $response->getData(true);
        $this->assertEquals(HttpStatus::ACCEPTED, $response->getStatusCode());
        $this->assertArrayHasAllKeys([
            'result' => [
                'message',
            ],
        ], $responseData);
    }
}
