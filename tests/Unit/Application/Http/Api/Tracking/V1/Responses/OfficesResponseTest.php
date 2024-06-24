<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Tracking\V1\Responses;

use App\Application\Http\Api\Tracking\V1\Responses\OfficesResponse;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Traits\AssertArrayHasAllKeys;

class OfficesResponseTest extends TestCase
{
    use AssertArrayHasAllKeys;

    /**
     * @test
     */
    public function it_creates_correct_response(): void
    {
        $offices = collect(OfficeFactory::many(2));

        $response = new OfficesResponse($offices);

        $responseData = $response->getData(true);
        $this->assertArrayHasAllKeys([
            'result' => [
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
        ], $responseData);
    }
}
