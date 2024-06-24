<?php

declare(strict_types=1);

namespace App\Application\Listeners\Notifier;

use App\Application\Events\PreferredTechResigned;
use App\Domain\Scheduling\ValueObjects\ResignedTechAssignment;
use App\Infrastructure\Services\Notification\EmailNotification\EmailNotificationService;
use App\Infrastructure\Services\Notification\Exceptions\EmailNotificationSendFailureException;
use App\Infrastructure\Services\Notification\Params\EmailNotificationParams;
use Illuminate\Support\Facades\Log;

class PreferredTechUnavailableNotifier
{
    private PreferredTechResigned $event;

    public function __construct(
        private EmailNotificationService $notificationService,
    ) {
    }

    /**
     * @param PreferredTechResigned $event
     *
     * @return void
     * @throws EmailNotificationSendFailureException
     */
    public function handle(PreferredTechResigned $event): void
    {
        $this->event = $event;

        $fromEmail = config('notification-service.recipients.from_email');

        if (empty($fromEmail)) {
            Log::error(__('messages.notification.from_email_not_set_in_config'));

            return;
        }

        $toEmails = $this->getCustomersEmails();

        if (empty($toEmails)) {
            Log::notice(__('messages.notification.unavailable_preferred_tech.no_customers_to_notify'));

            return;
        }

        $this->notificationService->send($this->generateEmailNotificationParams($fromEmail, $toEmails));

        $this->logEmailNotification();
    }

    /**
     * @param string $fromEmail
     * @param string[] $toEmails
     *
     * @return EmailNotificationParams
     */
    private function generateEmailNotificationParams(string $fromEmail, array $toEmails): EmailNotificationParams
    {
        return new EmailNotificationParams(
            toEmails: $toEmails,
            fromEmail: $fromEmail,
            subject: $this->getSubject(),
            body: $this->getBody(),
        );
    }

    /**
     * @return string[]
     */
    private function getCustomersEmails(): array
    {
        return $this->event->getResignedTechAssignments()
            ->map(fn (ResignedTechAssignment $resignedTechAssignment) => $resignedTechAssignment->customerEmail)
            ->filter()
            ->toArray();
    }

    private function getSubject(): string
    {
        return __('messages.notification.unavailable_preferred_tech.email_subject');
    }

    private function getBody(): string
    {
        return __('messages.notification.unavailable_preferred_tech.email_body');
    }

    private function logEmailNotification(): void
    {
        $customerDetails = $this->event->getResignedTechAssignments()->map(function (ResignedTechAssignment $resignedTechAssignment) {
            return [
                'customer_id' => $resignedTechAssignment->customerId,
                'email' => $resignedTechAssignment->customerEmail,
                'name' => $resignedTechAssignment->customerName,
            ];
        });

        Log::notice(__('messages.notification.unavailable_preferred_tech.log_email_notification'), [
            'customers' => $customerDetails,
            'office_id' => $this->event->getOfficeId(),
        ]);
    }
}
