<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Queries\Params;

use App\Domain\SharedKernel\Params\AbstractQueryParams;

class EmployeeInfoQueryParams extends AbstractQueryParams
{
    public function __construct(
        public readonly string|null $employee_id = null,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return $this->removeNullValuesAndEmptyArraysFromParamsArray([
            'Employee_ID' => $this->employee_id,
        ]);
    }
}
