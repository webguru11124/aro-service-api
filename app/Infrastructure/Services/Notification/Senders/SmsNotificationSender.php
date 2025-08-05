<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Notification\Senders;

use App\Domain\Notification\Entities\Recipient;
use App\Domain\Notification\Entities\Subscription;
use App\Infrastructure\Services\Notification\Exceptions\SmsNotificationSendFailureException;
use App\Infrastructure\Services\Notification\Params\SmsNotificationParams;
use App\Infrastructure\Services\Notification\SmsNotification\SmsNotificationService;

class SmsNotificationSender implements NotificationSender
{
    public function __construct(
        private SmsNotificationService $smsNotificationService,
    ) {
    }

    /**
     * @param NotificationSenderParams $params
     *
     * @return void
     * @throws SmsNotificationSendFailureException
     */
    public function send(NotificationSenderParams $params): void
    {
        $phones = $params->recipients
            ->filter(
                fn (Recipient $recipient) => !empty($recipient->getPhone()) && $recipient->getSubscriptions()->contains(
                    fn (Subscription $subscription) => $subscription->isSms()
                )
            )->map(
                fn (Recipient $recipient) => $recipient->getPhone()
            );

        if ($phones->isEmpty()) {
            return;
        }

        $this->smsNotificationService->send(new SmsNotificationParams(
            smsData: $params->message,
            toNumbers: $phones->toArray(),
        ));
    }
}
