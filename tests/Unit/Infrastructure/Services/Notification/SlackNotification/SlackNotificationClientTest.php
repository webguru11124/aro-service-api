<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Notification\SlackNotification;

use App\Infrastructure\Services\Notification\Exceptions\NotificationServiceClientException;
use App\Infrastructure\Services\Notification\SlackNotification\SlackNotificationClient;
use Exception;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SlackNotificationClientTest extends TestCase
{
    private SlackNotificationClient $slackNotificationClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->slackNotificationClient = new SlackNotificationClient('https://test-url.test');
    }

    /**
     * @test
     */
    public function it_send_post_successfully(): void
    {
        Http::fake([
            'https://test-url.test' => Http::response([]),
        ]);

        $this->slackNotificationClient->sendPost(['message' => 'test']);

        Http::assertSent(function (Request $request) {
            return $request->url() == 'https://test-url.test'
                && $request['message'] == 'test';
        });
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

        $this->slackNotificationClient->sendPost(['message' => 'test']);
    }
}
