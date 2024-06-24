<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Notification\Senders;

use App\Domain\Notification\Entities\Recipient;
use App\Domain\Notification\Entities\Subscription;
use App\Infrastructure\Services\Notification\EmailNotification\EmailNotificationService;
use App\Infrastructure\Services\Notification\Exceptions\EmailNotificationSendFailureException;
use App\Infrastructure\Services\Notification\Exceptions\FromEmailNotSetException;
use App\Infrastructure\Services\Notification\Params\EmailNotificationParams;

class EmailNotificationSender implements NotificationSender
{
    public function __construct(
        private EmailNotificationService $emailNotificationService,
    ) {
    }

    /**
     * @param NotificationSenderParams $params
     *
     * @return void
     * @throws EmailNotificationSendFailureException
     * @throws FromEmailNotSetException
     */
    public function send(NotificationSenderParams $params): void
    {
        $fromEmail = config('notification-service.recipients.from_email');

        if (empty($fromEmail)) {
            throw FromEmailNotSetException::instance();
        }

        $emails = $params->recipients
            ->filter(
                fn (Recipient $recipient) => !empty($recipient->getEmail()) && $recipient->getSubscriptions()->contains(
                    fn (Subscription $subscription) => $subscription->isEmail()
                )
            )
            ->map(
                fn (Recipient $recipient) => $recipient->getEmail()
            );

        if ($emails->isEmpty()) {
            return;
        }

        $this->emailNotificationService->send(
            new EmailNotificationParams(
                toEmails: $emails->toArray(),
                fromEmail: $fromEmail,
                subject: $params->title,
                body: $params->message,
            )
        );
    }
}
