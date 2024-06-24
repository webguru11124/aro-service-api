<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Notification\EmailNotification;

use App\Domain\Contracts\NotificationService;
use App\Infrastructure\Services\Notification\AbstractNotificationService;
use App\Infrastructure\Services\Notification\Exceptions\EmailNotificationSendFailureException;
use App\Infrastructure\Services\Notification\Exceptions\NotificationServiceClientException;
use App\Infrastructure\Services\Notification\Params\EmailNotificationParams;
use App\Infrastructure\Services\Notification\Params\NotificationParams;
use Illuminate\Support\Facades\Log;

class EmailNotificationService extends AbstractNotificationService implements NotificationService
{
    /**
     * Send email notifications to a list of recipients.
     *
     * @param EmailNotificationParams $notificationParams
     *
     * @return void
     * @throws EmailNotificationSendFailureException
     */
    public function send(NotificationParams $notificationParams): void
    {
        $failedRecipients = [];
        $recipientsEmails = $notificationParams->toEmails;

        foreach ($recipientsEmails as $email) {
            try {
                $payload = [
                    'type' => $notificationParams->type,
                    'toEmail' => $email,
                    'fromEmail' => $notificationParams->fromEmail,
                    'templateData' => [
                        'emailSubject' => $notificationParams->subject,
                        'emailBody' => $notificationParams->body,
                    ],
                    'emailTemplate' => $notificationParams->emailTemplate,
                ];

                $this->client->sendPost($payload);
            } catch (NotificationServiceClientException $exception) {
                Log::error(__('messages.notification.email_request_unsuccessful', [
                    'recipient' => $email,
                ]), [
                    'error' => $exception->getMessage(),
                ]);

                $failedRecipients[] = $email;
            }
        }

        if (!empty($failedRecipients)) {
            throw EmailNotificationSendFailureException::instance($failedRecipients);
        }
    }
}
