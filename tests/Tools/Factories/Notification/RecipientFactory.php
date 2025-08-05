<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Notification;

use Tests\Tools\Factories\AbstractFactory;
use App\Domain\Notification\Entities\Recipient;

class RecipientFactory extends AbstractFactory
{
    protected function single($overrides = []): Recipient
    {
        return new Recipient(
            id: $overrides['id'] ?? $this->faker->randomNumber(6),
            name: $overrides['name'] ?? $this->faker->firstName() . ' ' . $this->faker->lastName(),
            phone: $overrides['phone'] ?? $this->faker->phoneNumber(),
            email: $overrides['email'] ?? $this->faker->email(),
            subscriptions: $overrides['subscriptions'] ?? collect([SubscriptionFactory::make()]),
        );
    }
}
