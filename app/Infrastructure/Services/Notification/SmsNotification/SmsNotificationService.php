<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Notification\SmsNotification;

use App\Domain\Contracts\NotificationService;
use App\Infrastructure\Services\Notification\AbstractNotificationService;
use App\Infrastructure\Services\Notification\Exceptions\NotificationServiceClientException;
use App\Infrastructure\Services\Notification\Exceptions\SmsNotificationSendFailureException;
use App\Infrastructure\Services\Notification\Params\NotificationParams;
use App\Infrastructure\Services\Notification\Params\SmsNotificationParams;
use Illuminate\Support\Facades\Log;

class SmsNotificationService extends AbstractNotificationService implements NotificationService
{
    /**
     * Send a notification message to a list of recipients.
     *
     * Iterates over the recipients and sends the specified message
     * using the configured SMS API.
     *
     * @param SmsNotificationParams $notificationParams
     *
     * @return void
     * @throws SmsNotificationSendFailureException
     */
    public function send(NotificationParams $notificationParams): void
    {
        $failedRecipients = [];
        $recipients = $notificationParams->toNumbers;

        foreach ($recipients as $recipient) {
            $payload = [
                'type' => $notificationParams->type,
                'smsBus' => $notificationParams->smsBus,
                'toNum' => $recipient,
                'smsData' => $notificationParams->smsData,
            ];

            try {
                $this->client->sendPost($payload);
            } catch (NotificationServiceClientException $exception) {
                Log::error(__('messages.notification.sms_request_unsuccessful', [
                    'recipient' => $recipient,
                ]), [
                    'error' => $exception->getMessage(),
                ]);

                $failedRecipients[] = $recipient;
            }
        }

        if (!empty($failedRecipients)) {
            throw SmsNotificationSendFailureException::instance($failedRecipients);
        }
    }
}
