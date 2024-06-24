<?php

declare(strict_types=1);

namespace App\Application\Listeners\Log;

use App\Application\Events\SendNotifications\AbstractSendNotificationsJobEvent;
use App\Application\Events\SendNotifications\SendNotificationsJobEnded;
use App\Application\Events\SendNotifications\SendNotificationsJobFailed;
use App\Application\Events\SendNotifications\SendNotificationsJobStarted;
use App\Infrastructure\Helpers\DateTimeHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LogSendNotificationsJob
{
    private const string JOB_TITLE = 'SendNotificationsJob';

    private AbstractSendNotificationsJobEvent $event;

    /**
     * Handle the event.
     */
    public function handle(AbstractSendNotificationsJobEvent $event): void
    {
        $this->event = $event;

        match (true) {
            $event instanceof SendNotificationsJobStarted => $this->logJobStart(),
            $event instanceof SendNotificationsJobEnded => $this->logJobEnd(),
            $event instanceof SendNotificationsJobFailed => $this->logJobFailed(),
            default => null,
        };
    }

    private function logJobStart(): void
    {
        Log::info(self::JOB_TITLE . ' - START', [
            'job_name' => self::JOB_TITLE,
            'job_id' => $this->event->job?->getJobId(),
            'date' => $this->event->date->toDateString(),
            'message' => 'Start processing',
            'started_at' => (Carbon::now())->format(DateTimeHelper::DATE_TIME_FORMAT),
        ]);
    }

    private function logJobEnd(): void
    {
        Log::info(self::JOB_TITLE . ' - END', [
            'job_name' => self::JOB_TITLE,
            'job_id' => $this->event->job?->getJobId(),
            'date' => $this->event->date->toDateString(),
            'message' => 'End processing',
            'end_at' => (Carbon::now())->format(DateTimeHelper::DATE_TIME_FORMAT),
        ]);
    }

    private function logJobFailed(): void
    {
        Log::error(self::JOB_TITLE . ' - ERROR', [
            'job_name' => self::JOB_TITLE,
            'job_id' => $this->event->job?->getJobId(),
            'date' => $this->event->date->toDateString(),
            'failure_reason' => $this->event->exception,
            'failed_at' => (Carbon::now())->format(DateTimeHelper::DATE_TIME_FORMAT),
            'stack_trace' => $this->event->exception->getTrace(),
        ]);
    }
}
