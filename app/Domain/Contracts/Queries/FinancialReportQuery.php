<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Queries;

use App\Domain\Contracts\Queries\Params\FinancialReportQueryParams;
use App\Domain\SharedKernel\ValueObjects\FinancialReportEntry;
use Illuminate\Support\Collection;

interface FinancialReportQuery
{
    /**
     * Fetches data based on provided parameters.
     *
     * @param FinancialReportQueryParams $params
     *
     * @return Collection<FinancialReportEntry>
     */
    public function get(FinancialReportQueryParams $params): Collection;
}
