<?php

declare(strict_types=1);

namespace App\Domain\Notification\Entities;

use App\Domain\Notification\Enums\NotificationChannel;
use Illuminate\Support\Collection;

class Recipient
{
    /** @var Collection<Subscription> */
    private Collection $subscriptions;

    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string|null $phone,
        private readonly string|null $email,
        Collection|null $subscriptions = null,
    ) {
        $this->subscriptions = $subscriptions ?? collect();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getPhone(): string|null
    {
        return $this->phone;
    }

    /**
     * @return string|null
     */
    public function getEmail(): string|null
    {
        return $this->email;
    }

    /**
     * @return Collection<Subscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    /**
     * Returns true if recipient has subscription for the given notification type and channel.
     *
     * @param NotificationType $type
     * @param NotificationChannel $channel
     *
     * @return bool
     */
    public function hasSubscription(NotificationType $type, NotificationChannel $channel): bool
    {
        return $this->subscriptions->contains(
            fn (Subscription $subscription) => $subscription->isOf($type, $channel)
        );
    }
}
