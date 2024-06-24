<?php

declare(strict_types=1);

namespace App\Application\Listeners\Notifications;

use App\Application\Events\SchedulingSkipped;
use App\Domain\Notification\Enums\NotificationTypeEnum;

class SendSchedulingSkippedNotification extends AbstractSendNotification
{
    private const SEND_SCHEDULING_SKIPPED_NOTIFICATION_FEATURE_FLAG = 'isSendSchedulingSkippedNotificationEnabled';

    private SchedulingSkipped $event;

    /**
     * @param SchedulingSkipped $event
     *
     * @return void
     */
    public function handle(SchedulingSkipped $event): void
    {
        $this->event = $event;

        $this->process($event->office);
    }

    protected function isNotificationEnabled(): bool
    {
        return $this->featureFlagService->isFeatureEnabledForOffice(
            $this->event->office->getId(),
            self::SEND_SCHEDULING_SKIPPED_NOTIFICATION_FEATURE_FLAG,
        );
    }

    protected function getNotificationType(): NotificationTypeEnum
    {
        return NotificationTypeEnum::SCHEDULING_SKIPPED;
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
