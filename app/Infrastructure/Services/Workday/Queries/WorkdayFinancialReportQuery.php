<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Workday\Queries;

use App\Domain\Contracts\Queries\FinancialReportQuery;
use App\Domain\Contracts\Queries\Params\FinancialReportQueryParams;
use App\Domain\SharedKernel\ValueObjects\FinancialReportEntry;
use App\Infrastructure\Services\Workday\Exceptions\WorkdayErrorException;
use App\Infrastructure\Services\Workday\WorkdayAPIClient;
use Illuminate\Support\Collection;

class WorkdayFinancialReportQuery implements FinancialReportQuery
{
    private const string WORKDAY_LEDGER_WID = '49f014533a45017dee2252a9b601f1fb';
    private const string WORKDAY_COMPANY_WID = '49f014533a4501548b797b8e19010b85';

    private const WORKDAY_YEAR_WID = [
        2020 => '49f014533a45012f6d34801e2c01bf9d',
        2021 => '41daf157c66d01a8b7406e74cd00c35c',
        2022 => '6b507ba42e740100bd0d670904660000',
        2023 => 'a5c53122b4291001659accb12ae30000',
        2024 => 'e246c06389b51001f98ebed657db0000',
    ];

    private const WORKDAY_MONTH_WID = [
        'Jan' => '49f014533a45012e249f33df2b01129d',
        'Feb' => '49f014533a45019b598310df2b01089d',
        'Mar' => '49f014533a4501a3cf400cdf2b01079d',
        'Apr' => '49f014533a450174321e30df2b01119d',
        'May' => '49f014533a450146fed12cdf2b01109d',
        'Jun' => '49f014533a4501deebac08df2b01069d',
        'Jul' => '49f014533a45014d3c0305df2b01059d',
        'Aug' => '49f014533a450152c97d01df2b01049d',
        'Sep' => '49f014533a4501b86b4c29df2b010f9d',
        'Oct' => '49f014533a4501a57b9625df2b010e9d',
        'Nov' => '49f014533a4501b43b1722df2b010d9d',
        'Dec' => '49f014533a4501b7e58dfdde2b01039d',
    ];

    private const LEDGER_FIELDS_MAP = [
        'month' => 'Month',
        'year' => 'Year',
        'amount' => 'Amount',
        'cost_center_id' => 'Cost_center_id',
        'cost_center' => 'Cost_Center',
        'ledger_account_type' => 'Ledger_Acc_Type',
        'ledger_account_id' => 'Ledger_acc_id',
        'ledger_account' => 'Ledger_Account',
        'spend_category_id' => 'Spend_cat_id',
        'spend_category' => 'Spend_Category',
        'revenue_category_id' => 'Rev_Cat_ID',
        'revenue_category' => 'Revenue_Category',
        'service_center_id' => 'Service_Cen_ID',
        'service_center' => 'Service_center',
    ];

    public function __construct(
        private WorkdayAPIClient $workdayAPIClient
    ) {
    }

    /**
     * Gets financial report
     *
     * @param FinancialReportQueryParams $params
     *
     * @return Collection<FinancialReportEntry>
     * @throws WorkdayErrorException
     */
    public function get(FinancialReportQueryParams $params): Collection
    {
        $arrayResponse = $this->workdayAPIClient->get(
            config('workday.services.financial_report_url'),
            $this->getQueryParams($params)
        );

        return $this->buildReportEntries($arrayResponse);
    }

    /**
     * @param FinancialReportQueryParams $params
     *
     * @return mixed[]
     * @throws WorkdayErrorException
     */
    private function getQueryParams(FinancialReportQueryParams $params): array
    {
        $queryParams = $params->toArray();
        $year = $queryParams['year'];
        $month = $queryParams['month'];

        if (!array_key_exists($year, self::WORKDAY_YEAR_WID)) {
            throw new WorkdayErrorException(__('messages.workday.year_is_not_supported', [
                'year' => $year,
            ]));
        }

        return [
            'Company!WID' => self::WORKDAY_COMPANY_WID,
            'Ledger!WID' => self::WORKDAY_LEDGER_WID,
            'Year!WID' => self::WORKDAY_YEAR_WID[$year],
            'Period!WID' => self::WORKDAY_MONTH_WID[$month],
            'Include_System_Generated_Retained_Earnings' => 0,
            'Include_Beginning_Balance' => 0,
        ];
    }

    /**
     * @param mixed[] $report
     *
     * @return Collection<FinancialReportEntry>
     * @throws WorkdayErrorException
     */
    private function buildReportEntries(array $report): Collection
    {
        try {
            return collect(array_map(function ($entry) {
                $item = [];

                foreach (self::LEDGER_FIELDS_MAP as $key => $path) {
                    $item[$key] = data_get($entry, $path);
                }

                return $this->buildEntry($item);

            }, $report['Report_Entry']));
        } catch (\Throwable $ex) {
            throw new WorkdayErrorException(__('messages.workday.error_parsing_response', [
                'error' => $ex->getMessage(),
            ]));
        }
    }

    /**
     * @param mixed[] $data
     *
     * @return FinancialReportEntry
     */
    private function buildEntry(array $data): FinancialReportEntry
    {
        return new FinancialReportEntry(
            (int) $data['year'],
            $data['month'],
            (float) $data['amount'],
            $data['cost_center_id'],
            $data['cost_center'],
            $data['ledger_account_type'],
            $data['ledger_account_id'],
            $data['ledger_account'],
            $data['spend_category_id'],
            $data['spend_category'],
            $data['revenue_category_id'],
            $data['revenue_category'],
            $data['service_center_id'],
            $data['service_center']
        );
    }
}
