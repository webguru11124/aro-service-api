<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Repositories\Postgres;

use App\Domain\SharedKernel\ValueObjects\FinancialReportEntry;
use App\Infrastructure\Repositories\Postgres\PostgresFinancialReportRepository;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PostgresFinancialReportRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private const TABLE_NAME = PostgresDBInfo::MONTHLY_FINANCIAL_REPORTS_TABLE;

    private PostgresFinancialReportRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new PostgresFinancialReportRepository();
    }

    /**
     * @test
     *
     * ::saveFinancialReportData
     */
    public function it_saves_report_data(): void
    {
        $data = collect([
            new FinancialReportEntry(
                2021,
                'Jan',
                1000,
                'CC01',
                'Cost Center 1',
                'Type 1',
                'LA01',
                'Account 1',
                'CA01',
                'Category 1',
                'RA01',
                'Revenue Category 1',
                'SC01',
                'Service Center 1',
            ),
            new FinancialReportEntry(
                2021,
                'Jan',
                2000,
                'CC02',
                'Cost Center 2',
                'Type 2',
                'LA02',
                'Account 2',
                'CA02',
                'Category 2',
                'RA02',
                'Revenue Category 2',
                'SC02',
                'Service Center 2',
            ),
        ]);

        $this->repository->saveFinancialReportData($data);

        $this->assertDatabaseHas(self::TABLE_NAME, [
            'year' => 2021,
            'month' => 'Jan',
            'amount' => 1000,
            'cost_center_id' => 'CC01',
            'cost_center' => 'Cost Center 1',
            'ledger_account_type' => 'Type 1',
            'ledger_account_id' => 'LA01',
            'ledger_account' => 'Account 1',
            'spend_category_id' => 'CA01',
            'spend_category' => 'Category 1',
            'revenue_category_id' => 'RA01',
            'revenue_category' => 'Revenue Category 1',
            'service_center_id' => 'SC01',
            'service_center' => 'Service Center 1',
        ]);
        $this->assertDatabaseHas(self::TABLE_NAME, [
            'year' => 2021,
            'month' => 'Jan',
            'amount' => 2000,
            'cost_center_id' => 'CC02',
            'cost_center' => 'Cost Center 2',
            'ledger_account_type' => 'Type 2',
            'ledger_account_id' => 'LA02',
            'ledger_account' => 'Account 2',
            'spend_category_id' => 'CA02',
            'spend_category' => 'Category 2',
            'revenue_category_id' => 'RA02',
            'revenue_category' => 'Revenue Category 2',
            'service_center_id' => 'SC02',
            'service_center' => 'Service Center 2',
        ]);
    }

    /**
     * @test
     *
     * ::deleteFinancialReportForPeriod
     */
    public function it_deletes_existing_report_data_for_period(): void
    {
        $data = collect([
            new FinancialReportEntry(
                2021,
                'Jan',
                1000,
                'CC01',
                'Cost Center 1',
                'Type 1',
                'LA01',
                'Account 1',
                'CA01',
                'Category 1',
                'RA01',
                'Revenue Category 1',
                'SC01',
                'Service Center 1',
            ),
        ]);

        $this->repository->saveFinancialReportData($data);
        $this->repository->deleteFinancialReportForPeriod(2021, 'Jan');

        $this->assertDatabaseMissing(self::TABLE_NAME, [
            'year' => 2021,
            'month' => 'Jan',
        ]);
    }
}
