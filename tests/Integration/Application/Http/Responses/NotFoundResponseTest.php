<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Responses;

use Illuminate\Http\Response;
use Tests\TestCase;

class NotFoundResponseTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider methodProvider
     */
    public function it_returns_404_for_non_existing_resource(string $method): void
    {
        $response = $this->call($method, '/api/v1/non-existing-resource');

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public static function methodProvider(): array
    {
        return [
            ['GET'],
            ['POST'],
            ['PUT'],
            ['PATCH'],
            ['DELETE'],
            ['HEAD'],
            ['OPTIONS'],
        ];
    }
}
