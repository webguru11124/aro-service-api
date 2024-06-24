<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Calendar\V1\Responses;

use App\Application\Http\Api\Calendar\V1\Responses\GetAvatarResponse;
use Tests\TestCase;
use Tests\Traits\AssertArrayHasAllKeys;

class GetAvatarResponseTest extends TestCase
{
    use AssertArrayHasAllKeys;

    /**
     * @test
     */
    public function it_creates_correct_response(): void
    {
        $response = new GetAvatarResponse('avatarBase64');
        $responseData = $response->getData(true);
        $this->assertArrayHasAllKeys([
            'result' => [
                'avatarBase64',
            ],
        ], $responseData);
    }
}
