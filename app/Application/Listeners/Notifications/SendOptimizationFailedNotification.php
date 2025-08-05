<?php

declare(strict_types=1);

namespace App\Application\Listeners\Notifications;

use App\Application\Events\OptimizationJob\AbstractOptimizationJobEvent;
use App\Domain\Notification\Enums\NotificationTypeEnum;

class SendOptimizationFailedNotification extends AbstractSendNotification
{
    private const SEND_OPTIMIZATION_FAILURE_NOTIFICATION_FEATURE_FLAG = 'isSendOptimizationFailureNotificationEnabled';

    private AbstractOptimizationJobEvent $event;

    /**
     * @param AbstractOptimizationJobEvent $event
     *
     * @return void
     */
    public function handle(AbstractOptimizationJobEvent $event): void
    {
        $this->event = $event;

        $this->process($event->office);
    }

    protected function isNotificationEnabled(): bool
    {
        return $this->featureFlagService->isFeatureEnabledForOffice(
            $this->event->office->getId(),
            self::SEND_OPTIMIZATION_FAILURE_NOTIFICATION_FEATURE_FLAG,
        );
    }

    protected function getNotificationType(): NotificationTypeEnum
    {
        return NotificationTypeEnum::OPTIMIZATION_FAILED;
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
