<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Notification\Senders;

use Illuminate\Support\Collection;

readonly class NotificationSenderParams
{
    public function __construct(
        public string $title,
        public string $message,
        public Collection $recipients,
    ) {
    }
}
