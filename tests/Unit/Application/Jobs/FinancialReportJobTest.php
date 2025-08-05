<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Jobs;

use App\Application\Events\FinancialReport\FinancialReportJobEnded;
use App\Application\Events\FinancialReport\FinancialReportJobFailed;
use App\Application\Events\FinancialReport\FinancialReportJobStarted;
use App\Application\Jobs\FinancialReportJob;
use App\Domain\SharedKernel\ValueObjects\FinancialReportEntry;
use App\Domain\Contracts\Queries\Params\FinancialReportQueryParams;
use App\Infrastructure\Repositories\Postgres\PostgresFinancialReportRepository;
use App\Infrastructure\Services\Workday\Exceptions\WorkdayErrorException;
use App\Infrastructure\Services\Workday\Queries\WorkdayFinancialReportQuery;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class FinancialReportJobTest extends TestCase
{
    private int $year;
    private string $month;
    private FinancialReportJob $job;

    private MockInterface|WorkdayFinancialReportQuery $mockFinancialReportQuery;
    private MockInterface|PostgresFinancialReportRepository $mockPostgresFinancialReportRepository;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->year = Carbon::today()->year;
        $this->month = Carbon::today()->shortMonthName;
        $this->job = new FinancialReportJob($this->year, $this->month);

        $this->mockFinancialReportQuery = Mockery::mock(WorkdayFinancialReportQuery::class);
        $this->mockPostgresFinancialReportRepository = Mockery::mock(PostgresFinancialReportRepository::class);
    }

    /**
     * @test
     *
     * ::handle
     */
    public function it_updates_financial_report(): void
    {
        $reportData = collect([
            new FinancialReportEntry(
                year: $this->year,
                month: $this->month,
                amount: 1000,
                costCenterId: 'CC001',
                costCenter: 'Cost Center 1',
                ledgerAccountType: 'ledgerAccountType',
                ledgerAccountId: 'LA002',
                ledgerAccount: 'ledgerAccount',
                spendCategoryId: 'SC004',
                spendCategory: 'spendCategory',
                revenueCategoryId: 'RC004',
                revenueCategory: 'revenueCategory',
                serviceCenterId: 'SC005',
                serviceCenter: 'serviceCenter',
            ),
        ]);

        $this->mockFinancialReportQuery
            ->shouldReceive('get')
            ->with(Mockery::on(function ($arg) {
                return $arg instanceof FinancialReportQueryParams
                       && $arg->year === $this->year
                       && $arg->month === $this->month;
            }))
            ->once()
            ->andReturn($reportData);

        $this->mockPostgresFinancialReportRepository
            ->shouldReceive('deleteFinancialReportForPeriod')
            ->with($this->year, $this->month)
            ->once();
        $this->mockPostgresFinancialReportRepository
            ->shouldReceive('saveFinancialReportData')
            ->with($reportData)
            ->once();

        $this->executeJob();

        Event::assertDispatched(FinancialReportJobStarted::class);
        Event::assertDispatched(FinancialReportJobEnded::class);
    }

    /**
     * @test
     *
     * ::handle
     */
    public function it_logs_warning_when_financial_report_is_empty(): void
    {
        $reportData = collect();

        $this->mockFinancialReportQuery
            ->shouldReceive('get')
            ->with(Mockery::on(function ($arg) {
                return $arg instanceof FinancialReportQueryParams
                       && $arg->year === $this->year
                       && $arg->month === $this->month;
            }))
            ->once()
            ->andReturn($reportData);

        $this->mockPostgresFinancialReportRepository
            ->shouldReceive('deleteFinancialReportForPeriod')
            ->never();
        $this->mockPostgresFinancialReportRepository
            ->shouldReceive('saveFinancialReportData')
            ->never();

        Log::shouldReceive('warning')->once();

        $this->executeJob();

        Event::assertDispatched(FinancialReportJobStarted::class);
        Event::assertDispatched(FinancialReportJobEnded::class);
    }

    /**
     * @test
     *
     * ::handle
     */
    public function it_logs_error_when_workday_exception_occurred(): void
    {
        $this->mockFinancialReportQuery
            ->shouldReceive('get')
            ->with(Mockery::on(function ($arg) {
                return $arg instanceof FinancialReportQueryParams
                       && $arg->year === $this->year
                       && $arg->month === $this->month;
            }))
            ->once()
            ->andThrow(new WorkdayErrorException('Workday error'));

        $this->executeJob();

        Event::assertDispatched(FinancialReportJobStarted::class);
        Event::assertDispatched(FinancialReportJobEnded::class);
        Event::assertDispatched(FinancialReportJobFailed::class);
    }

    /**
     * @test
     *
     * ::failed
     */
    public function it_dispatches_event_on_failure(): void
    {
        $this->job->failed(new Exception('Test'));

        Event::assertDispatched(FinancialReportJobFailed::class);
    }

    private function executeJob(): void
    {
        $this->job->handle(
            $this->mockFinancialReportQuery,
            $this->mockPostgresFinancialReportRepository,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->job);
        unset($this->year);
        unset($this->month);
        unset($this->mockFinancialReportQuery);
        unset($this->mockPostgresFinancialReportRepository);
    }
}
