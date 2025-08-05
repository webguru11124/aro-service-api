<?php

declare(strict_types=1);

namespace App\Application\Listeners\Notifications;

use App\Application\Events\RouteExcluded;
use App\Domain\Notification\Enums\NotificationTypeEnum;

class SendRoutesExcludedNotification extends AbstractSendNotification
{
    private const SEND_ROUTES_EXCLUDED_FEATURE_FLAG = 'isSendRoutesExcludedNotificationEnabled';

    private RouteExcluded $event;

    /**
     * @param RouteExcluded $event
     *
     * @return void
     */
    public function handle(RouteExcluded $event): void
    {
        $this->event = $event;

        $this->process($event->office);
    }

    protected function isNotificationEnabled(): bool
    {
        return $this->featureFlagService->isFeatureEnabledForOffice(
            $this->event->office->getId(),
            self::SEND_ROUTES_EXCLUDED_FEATURE_FLAG,
        );
    }

    protected function getNotificationType(): NotificationTypeEnum
    {
        return NotificationTypeEnum::ROUTE_EXCLUDED;
    }

    protected function getMessageContent(): string
    {
        return __('messages.notifications.' . $this->getNotificationType()->value . '.message', [
            'office' => $this->event->office->getName(),
            'date' => $this->event->date->toDateString(),
            'route_ids' => implode(',', $this->event->routeIds),
            'reason' => $this->event->reason,
        ]);
    }
}
