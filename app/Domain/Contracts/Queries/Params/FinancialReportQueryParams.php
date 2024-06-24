<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Queries\Params;

use App\Domain\SharedKernel\Params\AbstractQueryParams;

class FinancialReportQueryParams extends AbstractQueryParams
{
    public function __construct(
        public readonly int $year,
        public readonly string $month,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return $this->removeNullValuesAndEmptyArraysFromParamsArray([
            'year' => $this->year,
            'month' => $this->month,
        ]);
    }
}
