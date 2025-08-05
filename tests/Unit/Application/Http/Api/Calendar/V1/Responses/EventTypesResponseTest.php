<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Calendar\V1\Responses;

use App\Application\Http\Api\Calendar\V1\Responses\EventTypesResponse;
use App\Domain\Calendar\Enums\EventType;
use Tests\TestCase;
use Tests\Traits\AssertArrayHasAllKeys;

class EventTypesResponseTest extends TestCase
{
    use AssertArrayHasAllKeys;

    /**
     * @test
     */
    public function it_creates_correct_response(): void
    {
        $response = new EventTypesResponse(collect(EventType::cases()));
        $responseData = $response->getData(true);

        $this->assertArrayHasAllKeys([
            'result' => [
                [
                    'id',
                    'name',
                ],
            ],
        ], $responseData);
    }
}
