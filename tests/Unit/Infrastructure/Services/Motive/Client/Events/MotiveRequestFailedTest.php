<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Motive\Client\Events;

use App\Infrastructure\Services\Motive\Client\Events\MotiveRequestFailed;
use Tests\TestCase;
use Throwable;

class MotiveRequestFailedTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_expected_exception(): void
    {
        $exception = $this->createMock(Throwable::class);

        $event = new MotiveRequestFailed(
            method: 'GET',
            url: 'https://example.com',
            options: ['headers' => ['Content-Type' => 'application/json']],
            exception: $exception
        );

        $this->assertInstanceOf(Throwable::class, $event->getException());
        $this->assertEquals($exception, $event->getException());
    }
}
