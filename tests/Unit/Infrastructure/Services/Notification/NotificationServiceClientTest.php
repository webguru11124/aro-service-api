<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Notification;

use App\Infrastructure\Services\Notification\Exceptions\NotificationServiceClientException;
use App\Infrastructure\Services\Notification\NotificationServiceClient;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationServiceClientTest extends TestCase
{
    private NotificationServiceClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('notification-service.auth.api_url', 'https://test-url.test');
        Config::set('notification-service.auth.api_bearer_token', 'test-token');

        $this->client = new NotificationServiceClient();
    }

    /**
     * @test
     */
    public function it_send_post_successfully(): void
    {
        Http::fake();

        $this->client->sendPost(['message' => 'test']);

        $this->expectNotToPerformAssertions();
    }

    /**
     * @test
     */
    public function it_throws_exception_when_send_post_fails(): void
    {
        Http::fake(function () {
            throw new Exception('Server Error');
        });

        $this->expectException(NotificationServiceClientException::class);

        $this->client->sendPost(['message' => 'test']);
    }
}
