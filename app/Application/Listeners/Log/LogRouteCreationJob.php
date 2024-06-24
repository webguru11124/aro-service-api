<?php

declare(strict_types=1);

namespace App\Application\Listeners\Log;

use App\Application\Events\RoutesCreationJob\AbstractRoutesCreationJobEvent;
use App\Application\Events\RoutesCreationJob\RoutesCreationJobEnded;
use App\Application\Events\RoutesCreationJob\RoutesCreationJobFailed;
use App\Application\Events\RoutesCreationJob\RoutesCreationJobStarted;
use App\Infrastructure\Helpers\DateTimeHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LogRouteCreationJob
{
    private const JOB_TITLE = 'RoutesCreationJob';

    private AbstractRoutesCreationJobEvent $event;

    /**
     * Handle the event.
     */
    public function handle(AbstractRoutesCreationJobEvent $event): void
    {
        $this->event = $event;

        match (true) {
            $event instanceof RoutesCreationJobStarted => $this->logJobStart(),
            $event instanceof RoutesCreationJobEnded => $this->logJobEnd(),
            $event instanceof RoutesCreationJobFailed => $this->logJobFailed(),
            default => null,
        };
    }

    private function logJobStart(): void
    {
        Log::info(self::JOB_TITLE . ' - START', [
            'job_name' => self::JOB_TITLE,
            'job_id' => $this->event->job?->getJobId(),
            'office_id' => $this->event->office->getId(),
            'office' => $this->event->office->getName(),
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
            'office_id' => $this->event->office->getId(),
            'office' => $this->event->office->getName(),
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
            'office_id' => $this->event->office->getId(),
            'office' => $this->event->office->getName(),
            'date' => $this->event->date->toDateString(),
            'failure_reason' => $this->event->exception,
            'failed_at' => (Carbon::now())->format(DateTimeHelper::DATE_TIME_FORMAT),
            'stack_trace' => $this->event->exception->getTrace(),
        ]);
    }
}