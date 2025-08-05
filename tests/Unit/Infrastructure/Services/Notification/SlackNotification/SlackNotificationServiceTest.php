<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Notification\SlackNotification;

use App\Infrastructure\Services\Notification\Exceptions\SlackNotificationSendFailureException;
use App\Infrastructure\Services\Notification\Params\SlackNotificationParams;
use App\Infrastructure\Services\Notification\SlackNotification\SlackNotificationClient;
use App\Infrastructure\Services\Notification\SlackNotification\SlackNotificationService;
use Exception;
use Mockery;
use Tests\TestCase;

class SlackNotificationServiceTest extends TestCase
{
    private SlackNotificationService $slackNotificationService;
    private SlackNotificationClient $mockSlackNotificationClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSlackNotificationClient = Mockery::mock(SlackNotificationClient::class);
        $this->slackNotificationService = new SlackNotificationService($this->mockSlackNotificationClient);
    }

    /**
     * @test
     */
    public function it_sends_slack_notifications_successfully(): void
    {
        $this->mockSlackNotificationClient
            ->shouldReceive('sendPost')
            ->with(['text' => 'test_body'])
            ->once();

        $params = new SlackNotificationParams(
            body: 'test_body',
        );

        $this->slackNotificationService->send($params);
    }

    /**
     * @test
     */
    public function it_handles_exceptions_when_sending_slack_notifications(): void
    {
        $this->mockSlackNotificationClient
            ->shouldReceive('sendPost')
            ->with(['text' => 'test_body'])
            ->andThrow(new Exception('test_exception'));

        $params = new SlackNotificationParams(
            body: 'test_body',
        );

        $this->expectException(SlackNotificationSendFailureException::class);

        $this->slackNotificationService->send($params);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->slackNotificationService, $this->mockSlackNotificationClient);
    }
}
