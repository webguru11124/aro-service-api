<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Motive\Client\Events;

use App\Infrastructure\Services\Motive\Client\Events\AbstractMotiveEvent;
use Tests\TestCase;

class AbstractMotiveEventTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_expected_getters(): void
    {
        $method = 'GET';
        $url = 'https://example.com/api';
        $options = ['headers' => ['Authorization' => 'Bearer token']];

        $event = new AbstractMotiveEvent($method, $url, $options);

        $this->assertEquals($method, $event->getMethod());
        $this->assertEquals($url, $event->getUrl());
        $this->assertEquals($options, $event->getOptions());
    }
}
