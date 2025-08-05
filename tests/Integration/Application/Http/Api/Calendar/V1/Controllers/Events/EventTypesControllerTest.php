<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\Calendar\V1\Controllers\Events;

use Tests\TestCase;
use Tests\Tools\JWTAuthTokenHelper;

class EventTypesControllerTest extends TestCase
{
    use JWTAuthTokenHelper;

    private const ROUTE_NAME = 'calendar.event-types.index';

    /** @var string[] */
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->headers = [
            'Authorization' => 'Bearer ' . $this->generateValidJwtToken(),
        ];
    }

    /**
     * @test
     */
    public function it_returns_200_on_success(): void
    {
        $response = $this->getJson(route(self::ROUTE_NAME), $this->headers);

        $response->assertOk();
        $response->assertJsonStructure([
            '_metadata' => ['success'],
        ]);
    }
}
