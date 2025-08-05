<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Notification\Params;

class SlackNotificationParams extends NotificationParams
{
    public function __construct(
        public readonly string|null $body = null,
    ) {
    }
}
