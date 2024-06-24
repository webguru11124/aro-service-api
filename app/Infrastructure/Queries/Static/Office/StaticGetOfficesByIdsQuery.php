<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\Static\Office;

use App\Domain\Contracts\Queries\Office\GetOfficesByIdsQuery;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use Illuminate\Support\Collection;

class StaticGetOfficesByIdsQuery implements GetOfficesByIdsQuery
{
    public function __construct(
        private StaticGetAllOfficesQuery $getAllOfficesQuery,
    ) {
    }

    /**
     * It returns a collection of offices by their ids
     *
     * @param int[] $ids
     *
     * @return Collection<Office>
     * @throws OfficeNotFoundException
     */
    public function get(array $ids): Collection
    {
        $offices = $this->getAllOfficesQuery->get()
            ->filter(fn (Office $office) => in_array($office->getId(), $ids));

        if ($offices->count() !== count($ids)) {
            $notFoundIds = array_diff(
                $ids,
                $offices->map(fn (Office $office) => $office->getId())->toArray()
            );

            throw OfficeNotFoundException::instance($notFoundIds);
        }

        return $offices;
    }
}
