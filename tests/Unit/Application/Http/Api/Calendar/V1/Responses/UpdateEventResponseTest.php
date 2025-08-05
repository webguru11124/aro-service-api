<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Calendar\V1\Responses;

use App\Application\Http\Api\Calendar\V1\Responses\UpdateEventResponse;
use Tests\TestCase;
use Tests\Tools\TestValue;
use Tests\Traits\AssertArrayHasAllKeys;

class UpdateEventResponseTest extends TestCase
{
    use AssertArrayHasAllKeys;

    /**
     * @test
     */
    public function it_creates_correct_response(): void
    {
        $response = new UpdateEventResponse(TestValue::EVENT_ID);
        $responseData = $response->getData(true);

        $this->assertArrayHasAllKeys([
            'result' => [
                'message',
                'id',
            ],
        ], $responseData);
    }
}
