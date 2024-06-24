<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Motive\Client\Events;

use App\Infrastructure\Services\Motive\Client\Events\MotiveResponseReceived;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

class MotiveResponseReceivedTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_expected_response(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $event = new MotiveResponseReceived(
            method: 'GET',
            url: 'https://example.com',
            options: ['headers' => ['Content-Type' => 'application/json']],
            response: $response
        );

        $this->assertInstanceOf(ResponseInterface::class, $event->getResponse());
        $this->assertEquals($response, $event->getResponse());
    }
}
