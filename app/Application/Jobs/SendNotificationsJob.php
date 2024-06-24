<?php

declare(strict_types=1);

namespace App\Application\Jobs;

use App\Application\Events\SendNotifications\SendNotificationsJobEnded;
use App\Application\Events\SendNotifications\SendNotificationsJobFailed;
use App\Application\Events\SendNotifications\SendNotificationsJobStarted;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Support\Collection;
use App\Domain\Notification\Entities\Recipient;
use App\Infrastructure\Formatters\NotificationFormatter;
use App\Domain\Contracts\Repositories\FleetRouteStateRepository;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Domain\Notification\Queries\OptimizationScoreRecipientsQuery;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Services\Notification\Params\EmailNotificationParams;
use App\Infrastructure\Services\Notification\Params\SlackNotificationParams;
use App\Infrastructure\Services\Notification\EmailNotification\EmailNotificationService;
use App\Infrastructure\Services\Notification\SlackNotification\SlackNotificationService;
use Throwable;

class SendNotificationsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private FleetRouteStateRepository $fleetRouteRepository;
    private EmailNotificationService $emailNotificationService;
    private OptimizationScoreRecipientsQuery $optimizationScoreRecipientsQuery;
    private SlackNotificationService $slackNotificationService;
    private NotificationFormatter $notificationFormatter;

    public function __construct(
        public readonly CarbonInterface $date,
        public readonly Collection $offices,
    ) {
        $this->onQueue(config('queue.queues.send-notifications'));
    }

    /**
     * Execute the job.
     */
    public function handle(
        FleetRouteStateRepository $fleetRouteRepository,
        EmailNotificationService $emailNotificationService,
        OptimizationScoreRecipientsQuery $optimizationScoreRecipientsQuery,
        SlackNotificationService $slackNotificationService,
        NotificationFormatter $notificationFormatter,
    ): void {
        SendNotificationsJobStarted::dispatch($this->date, $this->job);

        if (!$this->hasOffices()) {
            return;
        }

        $this->stateProperties(
            $fleetRouteRepository,
            $emailNotificationService,
            $optimizationScoreRecipientsQuery,
            $slackNotificationService,
            $notificationFormatter,
        );

        $this->sendEmailNotification($this->getNotificationBody('email'));
        $this->sendSlackNotification($this->getNotificationBody('slack'));

        SendNotificationsJobEnded::dispatch($this->date, $this->job);
    }

    private function stateProperties(
        FleetRouteStateRepository $fleetRouteRepository,
        EmailNotificationService $emailNotificationService,
        OptimizationScoreRecipientsQuery $optimizationScoreRecipientsQuery,
        SlackNotificationService $slackNotificationService,
        NotificationFormatter $notificationFormatter,
    ): void {
        $this->fleetRouteRepository = $fleetRouteRepository;
        $this->emailNotificationService = $emailNotificationService;
        $this->optimizationScoreRecipientsQuery = $optimizationScoreRecipientsQuery;
        $this->slackNotificationService = $slackNotificationService;
        $this->notificationFormatter = $notificationFormatter;
    }

    private function getNotificationBody(string $type): string
    {
        $officeScores = $this->getOfficeScores($this->fleetRouteRepository);

        return $this->notificationFormatter->format($this->date, $officeScores, $type);
    }

    private function sendEmailNotification(string $notificationBody): void
    {
        try {
            $emails = $this->getRecipientEmails($this->optimizationScoreRecipientsQuery);

            if (empty($emails)) {
                Log::notice(__('messages.notification.no_recipients_for_email_notification'));

                return;
            }

            $emailNotificationParams = $this->getEmailNotificationParams($notificationBody, $emails);
            $this->emailNotificationService->send($emailNotificationParams);

            Log::info(__('messages.notification.email_notification_sent', [
                'recipients' => implode(', ', $emails),
            ]));
        } catch (Throwable $e) {
            Log::error(__('messages.notification.failed_to_send_email_notification'), [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendSlackNotification(string $body): void
    {
        try {
            $slackNotificationParams = new SlackNotificationParams(
                body: $body,
            );

            $this->slackNotificationService->send($slackNotificationParams);
            Log::info(__('messages.notification.slack_notification_sent'));
        } catch (Throwable $e) {
            Log::error(__('messages.notification.failed_to_send_slack_notification'), [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param string $body
     * @param string[] $recipientEmails
     *
     * @return EmailNotificationParams
     * @throws Exception
     */
    private function getEmailNotificationParams(string $body, array $recipientEmails): EmailNotificationParams
    {
        $fromEmail = config('notification-service.recipients.from_email');

        if ($fromEmail === null) {
            throw new Exception(__('messages.notification.from_email_not_set_in_config'));
        }

        return new EmailNotificationParams(
            toEmails: $recipientEmails,
            fromEmail: $fromEmail,
            subject: __('messages.notification.optimization_score_summary_subject'),
            body: $body,
        );
    }

    /**
     * @param FleetRouteStateRepository $fleetRouteRepository
     *
     * @return array<int, array<string, string|float>>
     */
    private function getOfficeScores(FleetRouteStateRepository $fleetRouteRepository): array
    {
        $officeScores = [];

        /** @var Office $office */
        foreach ($this->offices as $office) {
            $optimizationScore = $this->getOptimizationScore($fleetRouteRepository, $office);

            if (is_null($optimizationScore)) {
                continue;
            }

            $officeScores[$office->getId()] = [
                'name' => $office->getName(),
                'score' => $optimizationScore,
            ];
        }

        return $officeScores;
    }

    /**
     * @param OptimizationScoreRecipientsQuery $optimizationScoreRecipientsQuery
     *
     * @return string[]
     */
    private function getRecipientEmails(OptimizationScoreRecipientsQuery $optimizationScoreRecipientsQuery): array
    {
        return $optimizationScoreRecipientsQuery->get()
            ->map(fn (Recipient $recipient) => $recipient->getEmail())
            ->toArray();
    }

    private function hasOffices(): bool
    {
        if ($this->offices->isEmpty()) {
            Log::notice(__('messages.notification.no_offices_for_score_notification'));

            return false;
        }

        return true;
    }

    private function getOptimizationScore(
        FleetRouteStateRepository $fleetRouteRepository,
        Office $office,
    ): float|null {
        $dateInOfficeTimezone = Carbon::parse($this->date->toDateString(), $office->getTimeZone());

        $fleetRouteState = $fleetRouteRepository->findByOfficeIdAndDate(
            $office->getId(),
            $this->date,
        );

        if (!$fleetRouteState || $fleetRouteState->getFleetRoutes()->isEmpty()) {
            Log::notice(__('messages.not_found.optimization_state', [
                'office' => $office->getName(),
                'office_id' => $office->getId(),
                'date' => $dateInOfficeTimezone->toDateString(),
            ]));

            return null;
        }

        return $fleetRouteState->getMetrics()?->getOptimizationScore();
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        SendNotificationsJobFailed::dispatch($this->date, $this->job, $exception);
    }
}
