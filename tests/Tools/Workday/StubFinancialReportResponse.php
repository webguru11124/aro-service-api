<?php

declare(strict_types=1);

namespace Tests\Tools\Workday;

class StubFinancialReportResponse
{
    /**
     * Simulate a sample array financial report info response from Workday
     *
     * @return array
     */
    public static function responseWithSuccess(): array
    {
        return [
            'Report_Entry' => [
                [
                    'Month' => 'Jan',
                    'Year' => '2024',
                    'Amount' => '15000.00',
                    'Cost_center_id' => 'CC1001',
                    'Cost_Center' => 'Marketing Department',
                    'Ledger_Acc_Type' => 'Expense',
                    'Ledger_acc_id' => 'LA201',
                    'Ledger_Account' => '2010:Travel Expenses',
                    'Spend_cat_id' => 'SC301',
                    'Spend_Category' => 'Software Purchases',
                    'Rev_Cat_ID' => 'RC401',
                    'Revenue_Category' => 'Product Sales',
                    'Service_Cen_ID' => 'SC501',
                    'Service_center' => 'Main Corporate Office',
                ],
            ],
        ];
    }

    /**
     * Simulate a sample json financial report info response from Workday
     *
     * @return string
     */
    public static function jsonResponse(): string
    {
        return json_encode(self::responseWithSuccess());
    }
}
