<?php

declare(strict_types=1);

namespace App\Application\Listeners\Log;

use App\Application\Events\FinancialReport\AbstractFinancialReportJobEvent;
use App\Application\Events\FinancialReport\FinancialReportJobEnded;
use App\Application\Events\FinancialReport\FinancialReportJobFailed;
use App\Application\Events\FinancialReport\FinancialReportJobStarted;
use App\Infrastructure\Helpers\DateTimeHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LogFinancialReportJob
{
    private const string JOB_TITLE = 'FinancialReportJob';

    private AbstractFinancialReportJobEvent $event;

    /**
     * Handle the event.
     */
    public function handle(AbstractFinancialReportJobEvent $event): void
    {
        $this->event = $event;

        match (true) {
            $event instanceof FinancialReportJobStarted => $this->logJobStart(),
            $event instanceof FinancialReportJobEnded => $this->logJobEnd(),
            $event instanceof FinancialReportJobFailed => $this->logJobFailed(),
            default => null,
        };
    }

    private function logJobStart(): void
    {
        Log::info(self::JOB_TITLE . ' - START', [
            'job_name' => self::JOB_TITLE,
            'job_id' => $this->event->job?->getJobId(),
            'year' => $this->event->year,
            'month' => $this->event->month,
            'message' => 'Start processing',
            'started_at' => (Carbon::now())->format(DateTimeHelper::DATE_TIME_FORMAT),
        ]);
    }

    private function logJobEnd(): void
    {
        Log::info(self::JOB_TITLE . ' - END', [
            'job_name' => self::JOB_TITLE,
            'job_id' => $this->event->job?->getJobId(),
            'year' => $this->event->year,
            'month' => $this->event->month,
            'message' => 'End processing',
            'end_at' => (Carbon::now())->format(DateTimeHelper::DATE_TIME_FORMAT),
        ]);
    }

    private function logJobFailed(): void
    {
        Log::error(self::JOB_TITLE . ' - ERROR', [
            'job_name' => self::JOB_TITLE,
            'job_id' => $this->event->job?->getJobId(),
            'year' => $this->event->year,
            'month' => $this->event->month,
            'failure_reason' => $this->event->exception,
            'failed_at' => (Carbon::now())->format(DateTimeHelper::DATE_TIME_FORMAT),
            'stack_trace' => $this->event->exception->getTrace(),
        ]);
    }
}
