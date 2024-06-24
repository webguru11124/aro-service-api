<?php

declare(strict_types=1);

namespace App\Domain\SharedKernel\ValueObjects;

class FinancialReportEntry
{
    public function __construct(
        public int $year,
        public string $month,
        public float $amount,
        public string|null $costCenterId,
        public string|null $costCenter,
        public string $ledgerAccountType,
        public string $ledgerAccountId,
        public string $ledgerAccount,
        public string|null $spendCategoryId,
        public string|null $spendCategory,
        public string|null $revenueCategoryId,
        public string|null $revenueCategory,
        public string|null $serviceCenterId,
        public string|null $serviceCenter,
    ) {
    }
}
