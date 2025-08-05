<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Notification\Entities;

use App\Domain\Notification\Entities\NotificationType;
use App\Domain\Notification\Entities\Recipient;
use App\Domain\Notification\Entities\Subscription;
use App\Domain\Notification\Enums\NotificationChannel;
use App\Domain\Notification\Enums\NotificationTypeEnum;
use Tests\TestCase;
use Tests\Tools\Factories\Notification\NotificationTypeFactory;
use Tests\Tools\Factories\Notification\SubscriptionFactory;

class RecipientTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_correctly_recipient(): void
    {
        $subscriptions = collect([SubscriptionFactory::make()]);

        $recipient = new Recipient(
            id: 1,
            name: 'John Doe',
            phone: '1234567890',
            email: 'example@example.example',
            subscriptions: $subscriptions,
        );

        $this->assertEquals(1, $recipient->getId());
        $this->assertEquals('John Doe', $recipient->getName());
        $this->assertEquals('1234567890', $recipient->getPhone());
        $this->assertEquals('example@example.example', $recipient->getEmail());
        $this->assertEquals($subscriptions, $recipient->getSubscriptions());
    }

    /**
     * @test
     */
    public function it_returns_true_when_there_is_subscription_for_notification_type_and_channel(): void
    {
        /** @var NotificationType $notificationType */
        $notificationType = NotificationTypeFactory::make([
            'name' => NotificationTypeEnum::OPTIMIZATION_FAILED->value,
        ]);

        /** @var Subscription $subscription */
        $subscription = SubscriptionFactory::make([
            'notificationType' => $notificationType,
            'notificationChannel' => NotificationChannel::EMAIL,
        ]);

        $recipient = new Recipient(
            id: 1,
            name: 'John Doe',
            phone: '1234567890',
            email: 'example@example.example',
            subscriptions: collect([$subscription]),
        );

        $this->assertEquals(1, $recipient->getId());
        $this->assertEquals('John Doe', $recipient->getName());
        $this->assertEquals('1234567890', $recipient->getPhone());
        $this->assertEquals('example@example.example', $recipient->getEmail());
        $this->assertTrue($recipient->hasSubscription($notificationType, NotificationChannel::EMAIL));
    }
}
