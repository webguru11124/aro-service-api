<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Notification\EmailNotification;

use App\Infrastructure\Services\Notification\EmailNotification\EmailNotificationService;
use App\Infrastructure\Services\Notification\Exceptions\EmailNotificationSendFailureException;
use App\Infrastructure\Services\Notification\Exceptions\NotificationServiceClientException;
use App\Infrastructure\Services\Notification\NotificationServiceClient;
use App\Infrastructure\Services\Notification\Params\EmailNotificationParams;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class EmailNotificationServiceTest extends TestCase
{
    private EmailNotificationService $emailNotificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = Mockery::mock(NotificationServiceClient::class);
        $this->emailNotificationService = new EmailNotificationService($this->mockClient);
    }

    /**
     * @test
     */
    public function it_sends_email_notifications_successfully(): void
    {
        $emailNotificationParams = new EmailNotificationParams(
            toEmails: ['email01@email.email'],
            fromEmail: 'no-reply@test.test',
            subject: 'test_subject',
            body: 'test_body',
            type: 'test_type',
            emailTemplate: 'test_template',
        );

        $this->mockClient->shouldReceive('sendPost')
            ->once();

        $this->emailNotificationService->send($emailNotificationParams);
    }

    /**
     * @test
     */
    public function it_handles_exceptions_when_sending_email_notifications(): void
    {
        $emailNotificationParams = new EmailNotificationParams(
            toEmails: ['email01@email.email'],
            fromEmail: 'no-reply@test.test',
            subject: 'test_subject',
            body: 'test_body',
            type: 'test_type',
            emailTemplate: 'test_template',
        );

        $this->mockClient->shouldReceive('sendPost')
            ->andThrow(NotificationServiceClientException::instance('test_error', ['test_payload']));

        $this->expectException(EmailNotificationSendFailureException::class);

        Log::shouldReceive('error')
            ->once();

        $this->emailNotificationService->send($emailNotificationParams);
    }
}
