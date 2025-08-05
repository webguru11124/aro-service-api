<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Queries\Office;

use App\Domain\SharedKernel\Entities\Office;
use Illuminate\Support\Collection;

interface GetAllOfficesQuery
{
    /**
     * It returns a collection of all offices.
     *
     * @return Collection<Office>
     */
    public function get(): Collection;
}
