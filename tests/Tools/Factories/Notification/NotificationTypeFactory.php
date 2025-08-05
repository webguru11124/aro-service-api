<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\Notification;

use App\Domain\Notification\Entities\NotificationType;
use App\Domain\Notification\Enums\NotificationTypeEnum;
use Tests\Tools\Factories\AbstractFactory;

class NotificationTypeFactory extends AbstractFactory
{
    protected function single($overrides = []): NotificationType
    {
        return new NotificationType(
            id: $overrides['id'] ?? $this->faker->randomNumber(6),
            name: $overrides['name'] ?? NotificationTypeEnum::OPTIMIZATION_FAILED->value,
        );
    }
}
