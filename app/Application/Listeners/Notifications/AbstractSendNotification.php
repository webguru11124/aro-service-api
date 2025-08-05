<?php

declare(strict_types=1);

namespace App\Application\Listeners\Notifications;

use App\Domain\Contracts\FeatureFlagService;
use App\Domain\Notification\Entities\Recipient;
use App\Domain\Notification\Enums\NotificationTypeEnum;
use App\Domain\Notification\Queries\NotificationTypeRecipientsQuery;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Services\Notification\Senders\NotificationSender;
use App\Infrastructure\Services\Notification\Senders\NotificationSenderParams;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class AbstractSendNotification
{
    /** @var NotificationSender[] */
    private array $senders;

    /** @var Collection<Recipient> */
    private Collection $recipients;

    public function __construct(
        protected FeatureFlagService $featureFlagService,
        private NotificationTypeRecipientsQuery $recipientsQuery,
        NotificationSender ...$senders
    ) {
        $this->senders = $senders;
    }

    /**
     * @param Office $office
     *
     * @return void
     */
    protected function process(Office $office): void
    {
        if (!$this->isNotificationEnabled()) {
            Log::notice(__('messages.notifications.' . $this->getNotificationType()->value . '.disabled', [
                'office' => $office->getName(),
                'office_id' => $office->getId(),
            ]));

            return;
        }

        $this->resolveRecipients();
        $this->sendNotifications();
    }

    protected function resolveRecipients(): void
    {
        $this->recipients = $this->recipientsQuery->get($this->getNotificationType());
    }

    private function sendNotifications(): void
    {
        foreach ($this->senders as $sender) {
            try {
                $sender->send(new NotificationSenderParams(
                    title: $this->getMessageTitle(),
                    message: $this->getMessageContent(),
                    recipients: $this->recipients,
                ));
            } catch (Throwable $exception) {
                Log::error(__('messages.notifications.failed_to_send', [
                    'error' => $exception->getMessage(),
                    'sender' => get_class($sender),
                ]));
            }
        }
    }

    protected function getMessageTitle(): string
    {
        return __('messages.notifications.' . $this->getNotificationType()->value . '.title');
    }

    protected function getMessageContent(): string
    {
        return __('messages.notifications.' . $this->getNotificationType()->value . '.message');
    }

    abstract protected function isNotificationEnabled(): bool;

    abstract protected function getNotificationType(): NotificationTypeEnum;
}
