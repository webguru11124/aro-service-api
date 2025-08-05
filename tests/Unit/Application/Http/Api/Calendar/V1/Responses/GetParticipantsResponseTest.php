<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Calendar\V1\Responses;

use App\Application\Http\Api\Calendar\V1\Responses\GetParticipantsResponse;
use App\Domain\Calendar\Entities\Participant;
use Tests\TestCase;
use Tests\Tools\TestValue;
use Tests\Traits\AssertArrayHasAllKeys;

class GetParticipantsResponseTest extends TestCase
{
    use AssertArrayHasAllKeys;

    /**
     * @test
     */
    public function it_creates_correct_response(): void
    {
        $participantsCollection = collect([new Participant(1, 'John Doe', true, TestValue::WORKDAY_ID)]);
        $response = new GetParticipantsResponse($participantsCollection);

        $responseData = $response->getData(true);

        $this->assertArrayHasAllKeys([
            'result' => [
                'participants' => [
                    [
                        'id',
                        'name',
                        'is_invited',
                        'external_id',
                    ],
                ],
            ],
        ], $responseData);
    }
}
