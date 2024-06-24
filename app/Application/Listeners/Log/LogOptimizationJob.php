<?php

declare(strict_types=1);

namespace App\Application\Listeners\Log;

use App\Application\Events\OptimizationJob\AbstractOptimizationJobEvent;
use App\Application\Events\OptimizationJob\OptimizationJobFailedToUpdateLockedAppointment;
use App\Application\Events\OptimizationJob\OptimizationJobEnded;
use App\Application\Events\OptimizationJob\OptimizationJobFailed;
use App\Application\Events\OptimizationJob\OptimizationJobStarted;
use App\Infrastructure\Helpers\DateTimeHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LogOptimizationJob
{
    private AbstractOptimizationJobEvent $event;

    /**
     * Handle the event.
     */
    public function handle(AbstractOptimizationJobEvent $event): void
    {
        $this->event = $event;

        match (true) {
            $event instanceof OptimizationJobStarted => $this->logJobStart(),
            $event instanceof OptimizationJobEnded => $this->logJobEnd(),
            $event instanceof OptimizationJobFailed => $this->logJobFailed(),
            $event instanceof OptimizationJobFailedToUpdateLockedAppointment => $this->logFailedToUpdateLockedAppointment(),
            default => null,
        };
    }

    private function logJobStart(): void
    {
        Log::info('OptimizeRoutesJob Processing - START', [
            'job_name' => 'OptimizeRoutesJob',
            'job_id' => $this->event->job?->getJobId(),
            'office_id' => $this->event->office->getId(),
            'date' => $this->event->date->toDateString(),
            'message' => 'Start processing',
            'started_at' => (Carbon::now())->format(DateTimeHelper::DATE_TIME_FORMAT),
        ]);
    }

    private function logJobEnd(): void
    {
        Log::info('OptimizeRoutesJob Processing - END', [
            'job_name' => 'OptimizeRoutesJob',
            'job_id' => $this->event->job?->getJobId(),
            'office_id' => $this->event->office->getId(),
            'date' => $this->event->date->toDateString(),
            'message' => 'End processing',
            'end_at' => (Carbon::now())->format(DateTimeHelper::DATE_TIME_FORMAT),
        ]);
    }

    private function logJobFailed(): void
    {
        Log::error('OptimizeRoutesJob Processing - ERROR', [
            'job_name' => 'OptimizeRoutesJob',
            'job_id' => $this->event->job?->getJobId(),
            'office_id' => $this->event->office->getId(),
            'date' => $this->event->date->toDateString(),
            'failure_reason' => $this->event->exception,
            'failed_at' => (Carbon::now())->format(DateTimeHelper::DATE_TIME_FORMAT),
            'stack_trace' => $this->event->exception->getTrace(),
        ]);
    }

    private function logFailedToUpdateLockedAppointment(): void
    {
        Log::error(
            'OptimizeRoutesJob Processing - Failed To Update Locked Appointment',
            [
                'office_id' => $this->event->office->getId(),
                'office_name' => $this->event->office->getName(),
                'date' => $this->event->date->toDateString(),
            ]
        );
    }
}
