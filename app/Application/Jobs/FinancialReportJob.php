<?php

declare(strict_types=1);

namespace App\Application\Jobs;

use App\Application\Events\FinancialReport\FinancialReportJobEnded;
use App\Application\Events\FinancialReport\FinancialReportJobFailed;
use App\Application\Events\FinancialReport\FinancialReportJobStarted;
use App\Domain\Contracts\Queries\FinancialReportQuery;
use App\Domain\Contracts\Queries\Params\FinancialReportQueryParams;
use App\Infrastructure\Repositories\Postgres\PostgresFinancialReportRepository;
use App\Infrastructure\Services\Workday\Exceptions\WorkdayErrorException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class FinancialReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600; // 10 min

    public function __construct(
        public int $year,
        public string $month,
    ) {
        $this->onQueue(config('queue.queues.build-reports'));
    }

    /**
     * Queries financial data and updates the report
     */
    public function handle(
        FinancialReportQuery $reportQuery,
        PostgresFinancialReportRepository $reportRepository,
    ): void {
        FinancialReportJobStarted::dispatch($this->year, $this->month, $this->job);

        try {
            $params = new FinancialReportQueryParams($this->year, $this->month);
            $reportData = $reportQuery->get($params);

            if ($reportData->isEmpty()) {
                Log::warning(__('messages.workday.financial_report_empty', [
                    'year' => $this->year,
                    'month' => $this->month,
                ]));
            } else {
                $reportRepository->deleteFinancialReportForPeriod($this->year, $this->month);
                $reportRepository->saveFinancialReportData($reportData);
            }
        } catch (WorkdayErrorException $exception) {
            FinancialReportJobFailed::dispatch($this->year, $this->month, $this->job, $exception);
        }

        FinancialReportJobEnded::dispatch($this->year, $this->month, $this->job);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        FinancialReportJobFailed::dispatch($this->year, $this->month, $this->job, $exception);
    }
}
