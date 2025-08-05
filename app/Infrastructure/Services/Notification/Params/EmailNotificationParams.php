<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Notification\Params;

class EmailNotificationParams extends NotificationParams
{
    /**
     * @param string $emailTemplate
     * @param string[] $toEmails
     * @param string $fromEmail
     * @param string $subject
     * @param string $body
     * @param string $type
     */
    public function __construct(
        public readonly array $toEmails,
        public readonly string $fromEmail,
        public readonly string $subject,
        public readonly string $body,
        public readonly string $type = 'email',
        public readonly string $emailTemplate = 'basicTemplate',
    ) {
    }
}
