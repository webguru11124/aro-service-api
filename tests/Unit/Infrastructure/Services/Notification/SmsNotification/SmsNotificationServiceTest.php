<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Notification\SmsNotification;

use App\Infrastructure\Services\Notification\Exceptions\NotificationServiceClientException;
use App\Infrastructure\Services\Notification\Exceptions\SmsNotificationSendFailureException;
use App\Infrastructure\Services\Notification\NotificationServiceClient;
use App\Infrastructure\Services\Notification\Params\SmsNotificationParams;
use App\Infrastructure\Services\Notification\SmsNotification\SmsNotificationService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class SmsNotificationServiceTest extends TestCase
{
    private const TEST_RECIPIENTS = ['8011234567', '8012345678'];

    private SmsNotificationService $smsNotificationService;
    private NotificationServiceClient|MockInterface $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = Mockery::mock(NotificationServiceClient::class);
        $this->smsNotificationService = new SmsNotificationService($this->mockClient);
    }

    /**
     * @test
     */
    public function it_sends_sms_notifications_successfully(): void
    {
        $smsNotificationParams = new SmsNotificationParams(
            smsData: 'test_data',
            toNumbers: self::TEST_RECIPIENTS,
            type: 'test_type',
            smsBus: 'test_bus'
        );

        $this->mockClient->shouldReceive('sendPost')
            ->times(count(self::TEST_RECIPIENTS));

        $this->smsNotificationService->send($smsNotificationParams);
    }

    /**
     * @test
     */
    public function it_handles_exceptions_when_sending_sms_notifications(): void
    {
        $smsNotificationParams = new SmsNotificationParams(
            smsData: 'test_data',
            toNumbers: self::TEST_RECIPIENTS,
            type: 'test_type',
            smsBus: 'test_bus'
        );

        $this->mockClient->shouldReceive('sendPost')
            ->times(count(self::TEST_RECIPIENTS))
            ->andThrow(NotificationServiceClientException::instance('test_error', ['test_payload']));

        $this->expectException(SmsNotificationSendFailureException::class);

        Log::shouldReceive('error')
            ->times(count(self::TEST_RECIPIENTS));

        $this->smsNotificationService->send($smsNotificationParams);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->smsNotificationService);
        unset($this->mockClient);
    }
}
