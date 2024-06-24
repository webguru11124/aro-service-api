<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Queries;

use App\Domain\Scheduling\Entities\Plan;
use Illuminate\Support\Collection;

interface PlansQuery
{
    /**
     * Returns list of active plans
     *
     * @return Collection<Plan>
     */
    public function get(): Collection;
}
