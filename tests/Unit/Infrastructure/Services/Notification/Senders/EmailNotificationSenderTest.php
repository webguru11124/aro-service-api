<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Notification\Senders;

use App\Domain\Notification\Enums\NotificationChannel;
use App\Infrastructure\Services\Notification\EmailNotification\EmailNotificationService;
use App\Infrastructure\Services\Notification\Exceptions\FromEmailNotSetException;
use App\Infrastructure\Services\Notification\Params\EmailNotificationParams;
use App\Infrastructure\Services\Notification\Senders\EmailNotificationSender;
use App\Infrastructure\Services\Notification\Senders\NotificationSenderParams;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\Notification\RecipientFactory;
use Tests\Tools\Factories\Notification\SubscriptionFactory;

class EmailNotificationSenderTest extends TestCase
{
    private const string FROM_EMAIL = 'from_email';
    private const string TITLE = 'title';
    private const string MESSAGE = 'message';

    private MockInterface|EmailNotificationService $mockEmailNotificationService;

    private EmailNotificationSender $sender;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('notification-service.recipients.from_email', self::FROM_EMAIL);

        $this->mockEmailNotificationService = Mockery::mock(EmailNotificationService::class);
        $this->sender = new EmailNotificationSender($this->mockEmailNotificationService);
    }

    /**
     * @test
     */
    public function it_sends_email_notifications(): void
    {
        $recipients = collect([
            RecipientFactory::make([
                'email' => 'email1',
                'subscriptions' => collect([SubscriptionFactory::make([
                    'notificationChannel' => NotificationChannel::EMAIL,
                ])]),
            ]),
            RecipientFactory::make([
                'email' => 'email2',
                'subscriptions' => collect([SubscriptionFactory::make([
                    'notificationChannel' => NotificationChannel::EMAIL,
                ])]),
            ]),
        ]);

        $this->mockEmailNotificationService
            ->shouldReceive('send')
            ->once()
            ->withArgs(function (EmailNotificationParams $params) {
                return $params->toEmails === ['email1', 'email2']
                    && $params->fromEmail === self::FROM_EMAIL
                    && $params->subject === self::TITLE
                    && $params->body === self::MESSAGE;
            });

        $this->sender->send(new NotificationSenderParams(
            title: self::TITLE,
            message: self::MESSAGE,
            recipients: $recipients,
        ));
    }

    /**
     * @test
     */
    public function it_does_not_send_notifications_when_no_recipients_found(): void
    {
        $recipients = collect([
            RecipientFactory::make([
                'email' => '',
                'subscriptions' => collect([SubscriptionFactory::make([
                    'notificationChannel' => NotificationChannel::EMAIL,
                ])]),
            ]),
            RecipientFactory::make([
                'email' => 'email2',
                'subscriptions' => collect([SubscriptionFactory::make([
                    'notificationChannel' => NotificationChannel::SMS,
                ])]),
            ]),
        ]);

        $this->mockEmailNotificationService
            ->shouldReceive('send')
            ->never();

        $this->sender->send(new NotificationSenderParams(
            title: self::TITLE,
            message: self::MESSAGE,
            recipients: $recipients,
        ));
    }

    /**
     * @test
     */
    public function it_throws_exception_when_no_from_email_set(): void
    {
        Config::set('notification-service.recipients.from_email', '');

        $this->mockEmailNotificationService
            ->shouldReceive('send')
            ->never();

        $this->expectException(FromEmailNotSetException::class);

        $this->sender->send(new NotificationSenderParams(
            title: self::TITLE,
            message: self::MESSAGE,
            recipients: collect([RecipientFactory::make()]),
        ));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->mockEmailNotificationService);
        unset($this->sender);
    }
}
