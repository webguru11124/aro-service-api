<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\Static\Office;

use App\Domain\Contracts\Queries\Office\GetOfficeQuery;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\OfficeNotFoundException;

class StaticGetOfficeQuery implements GetOfficeQuery
{
    public function __construct(
        private readonly StaticGetAllOfficesQuery $getAllOfficesQuery,
    ) {
    }

    /**
     * Get an office by its id.
     *
     * @param int $officeId
     *
     * @return Office
     * @throws OfficeNotFoundException
     */
    public function get(int $officeId): Office
    {
        $office = $this->getAllOfficesQuery->get()
            ->filter(fn (Office $office) => $office->getId() === $officeId)
            ->first();

        if (empty($office)) {
            throw OfficeNotFoundException::instance([$officeId]);
        }

        return $office;
    }
}
