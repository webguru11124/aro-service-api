<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Postgres;

use App\Domain\SharedKernel\ValueObjects\FinancialReportEntry;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PostgresFinancialReportRepository
{
    /**
     * Delete financial report data for the given period
     *
     * @param int $year
     * @param string $month
     *
     * @return void
     */
    public function deleteFinancialReportForPeriod(int $year, string $month): void
    {
        DB::table(PostgresDBInfo::MONTHLY_FINANCIAL_REPORTS_TABLE)
            ->where('year', $year)
            ->where('month', $month)
            ->delete();
    }

    /**
     * Save financial report data
     *
     * @param Collection<FinancialReportEntry> $reportData
     *
     * @return void
     */
    public function saveFinancialReportData(Collection $reportData): void
    {
        $data = $reportData->map(function (FinancialReportEntry $entry) {
            return [
                'year' => $entry->year,
                'month' => $entry->month,
                'amount' => $entry->amount,
                'cost_center_id' => $entry->costCenterId,
                'cost_center' => $entry->costCenter,
                'ledger_account_type' => $entry->ledgerAccountType,
                'ledger_account_id' => $entry->ledgerAccountId,
                'ledger_account' => $entry->ledgerAccount,
                'spend_category_id' => $entry->spendCategoryId,
                'spend_category' => $entry->spendCategory,
                'revenue_category_id' => $entry->revenueCategoryId,
                'revenue_category' => $entry->revenueCategory,
                'service_center_id' => $entry->serviceCenterId,
                'service_center' => $entry->serviceCenter,
            ];
        });

        DB::table(PostgresDBInfo::MONTHLY_FINANCIAL_REPORTS_TABLE)
            ->insert($data->toArray());
    }
}
