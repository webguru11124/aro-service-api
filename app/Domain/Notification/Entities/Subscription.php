<?php

declare(strict_types=1);

namespace App\Domain\Notification\Entities;

use App\Domain\Notification\Enums\NotificationChannel;

class Subscription
{
    public function __construct(
        private readonly int $id,
        private readonly NotificationType $notificationType,
        private readonly NotificationChannel $notificationChannel,
    ) {
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return NotificationType
     */
    public function getNotificationType(): NotificationType
    {
        return $this->notificationType;
    }

    /**
     * @return bool
     */
    public function isSms(): bool
    {
        return $this->notificationChannel === NotificationChannel::SMS;
    }

    /**
     * @return bool
     */
    public function isEmail(): bool
    {
        return $this->notificationChannel === NotificationChannel::EMAIL;
    }

    /**
     * Returns true if the subscription is of the given type and channel
     *
     * @param NotificationType $type
     * @param NotificationChannel $channel
     *
     * @return bool
     */
    public function isOf(NotificationType $type, NotificationChannel $channel): bool
    {
        return $this->notificationType->getId() === $type->getId() && $this->notificationChannel === $channel;
    }
}
