<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Notification;

use App\Domain\Notification\Entities\Subscription;
use App\Domain\Notification\Enums\NotificationChannel;
use Tests\Tools\Factories\AbstractFactory;

class SubscriptionFactory extends AbstractFactory
{
    protected function single($overrides = []): Subscription
    {
        return new Subscription(
            id: $overrides['id'] ?? $this->faker->randomNumber(6),
            notificationType: $overrides['notificationType'] ?? NotificationTypeFactory::make(),
            notificationChannel: $overrides['notificationChannel'] ?? NotificationChannel::tryFrom($this->faker->randomElement(['sms', 'email'])),
        );
    }
}
