<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Web\Controllers;

use Tests\TestCase;

class RouteHomeEndpointTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_200_status_code_for_home_route(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
    }
}
