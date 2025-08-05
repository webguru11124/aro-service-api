<?php

declare(strict_types=1);

namespace App\Application\Listeners\Notifications;

use App\Application\Events\OptimizationSkipped;
use App\Domain\Notification\Enums\NotificationTypeEnum;

class SendOptimizationSkippedNotification extends AbstractSendNotification
{
    private const SEND_OPTIMIZATION_SKIPPED_NOTIFICATION_FEATURE_FLAG = 'isOptimizationSkippedNotificationEnabled';

    private OptimizationSkipped $event;

    /**
     * @param OptimizationSkipped $event
     *
     * @return void
     */
    public function handle(OptimizationSkipped $event): void
    {
        $this->event = $event;

        $this->process($event->office);
    }

    protected function isNotificationEnabled(): bool
    {
        return $this->featureFlagService->isFeatureEnabledForOffice(
            $this->event->office->getId(),
            self::SEND_OPTIMIZATION_SKIPPED_NOTIFICATION_FEATURE_FLAG,
        );
    }

    protected function getNotificationType(): NotificationTypeEnum
    {
        return NotificationTypeEnum::OPTIMIZATION_SKIPPED;
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
