<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Notification\Entities;

use App\Domain\Notification\Entities\Subscription;
use App\Domain\Notification\Enums\NotificationChannel;
use App\Domain\Notification\Enums\NotificationTypeEnum;
use Tests\TestCase;
use Tests\Tools\Factories\Notification\NotificationTypeFactory;

class SubscriptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_subscription_correctly(): void
    {
        $subscription = new Subscription(
            id: 1,
            notificationType: NotificationTypeFactory::make(),
            notificationChannel: NotificationChannel::EMAIL,
        );

        $this->assertEquals(1, $subscription->getId());
        $this->assertEquals(NotificationTypeEnum::OPTIMIZATION_FAILED->value, $subscription->getNotificationType()->getName());
        $this->assertFalse($subscription->isSms());
        $this->assertTrue($subscription->isEmail());
    }
}
