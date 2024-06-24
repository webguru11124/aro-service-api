<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Notification\Senders;

use App\Domain\Notification\Enums\NotificationChannel;
use App\Infrastructure\Services\Notification\EmailNotification\EmailNotificationService;
use App\Infrastructure\Services\Notification\Params\SmsNotificationParams;
use App\Infrastructure\Services\Notification\Senders\NotificationSenderParams;
use App\Infrastructure\Services\Notification\Senders\SmsNotificationSender;
use App\Infrastructure\Services\Notification\SmsNotification\SmsNotificationService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\Notification\RecipientFactory;
use Tests\Tools\Factories\Notification\SubscriptionFactory;

class SmsNotificationSenderTest extends TestCase
{
    private const string TITLE = 'title';
    private const string MESSAGE = 'message';

    private MockInterface|EmailNotificationService $mockSmsNotificationService;

    private SmsNotificationSender $sender;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSmsNotificationService = Mockery::mock(SmsNotificationService::class);
        $this->sender = new SmsNotificationSender($this->mockSmsNotificationService);
    }

    /**
     * @test
     */
    public function it_sends_sms_notifications(): void
    {
        $recipients = collect([
            RecipientFactory::make([
                'phone' => 'phone1',
                'subscriptions' => collect([SubscriptionFactory::make([
                    'notificationChannel' => NotificationChannel::SMS,
                ])]),
            ]),
            RecipientFactory::make([
                'phone' => 'phone2',
                'subscriptions' => collect([SubscriptionFactory::make([
                    'notificationChannel' => NotificationChannel::SMS,
                ])]),
            ]),
        ]);

        $this->mockSmsNotificationService
            ->shouldReceive('send')
            ->once()
            ->withArgs(function (SmsNotificationParams $params) {
                return $params->smsData === self::MESSAGE
                    && $params->toNumbers === ['phone1', 'phone2'];
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
                'phone' => '',
                'subscriptions' => collect([SubscriptionFactory::make([
                    'notificationChannel' => NotificationChannel::SMS,
                ])]),
            ]),
            RecipientFactory::make([
                'phone' => 'phone2',
                'subscriptions' => collect([SubscriptionFactory::make([
                    'notificationChannel' => NotificationChannel::EMAIL,
                ])]),
            ]),
        ]);

        $this->mockSmsNotificationService
            ->shouldReceive('send')
            ->never();

        $this->sender->send(new NotificationSenderParams(
            title: self::TITLE,
            message: self::MESSAGE,
            recipients: $recipients,
        ));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->mockSmsNotificationService);
        unset($this->sender);
    }
}
