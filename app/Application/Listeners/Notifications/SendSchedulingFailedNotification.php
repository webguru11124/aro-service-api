<?php

declare(strict_types=1);

namespace App\Application\Listeners\Notifications;

use App\Application\Events\ScheduleAppointments\ScheduleAppointmentsJobFailed;
use App\Domain\Notification\Enums\NotificationTypeEnum;

class SendSchedulingFailedNotification extends AbstractSendNotification
{
    private const SEND_SCHEDULING_FAILED_NOTIFICATION_FEATURE_FLAG = 'isSendSchedulingFailureNotificationEnabled';

    private ScheduleAppointmentsJobFailed $event;

    /**
     * @param ScheduleAppointmentsJobFailed $event
     *
     * @return void
     */
    public function handle(ScheduleAppointmentsJobFailed $event): void
    {
        $this->event = $event;

        $this->process($event->office);
    }

    protected function isNotificationEnabled(): bool
    {
        return $this->featureFlagService->isFeatureEnabledForOffice(
            $this->event->office->getId(),
            self::SEND_SCHEDULING_FAILED_NOTIFICATION_FEATURE_FLAG,
        );
    }

    protected function getNotificationType(): NotificationTypeEnum
    {
        return NotificationTypeEnum::SCHEDULING_FAILED;
    }

    protected function getMessageContent(): string
    {
        return __('messages.notifications.' . $this->getNotificationType()->value . '.message', [
            'office' => $this->event->office->getName(),
            'date' => $this->event->date->toDateString(),
            'exception' => $this->event->exception->getMessage(),
        ]);
    }
}
