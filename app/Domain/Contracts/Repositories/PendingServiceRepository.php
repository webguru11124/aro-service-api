<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repositories;

use App\Domain\Scheduling\Entities\PendingService;
use App\Domain\Scheduling\Entities\Plan;
use App\Domain\SharedKernel\Entities\Office;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface PendingServiceRepository
{
    /**
     * Returns collection of pending services for office and due date
     *
     * @param Office $office
     * @param CarbonInterface $date
     * @param Plan $plan
     *
     * @return Collection<PendingService>
     */
    public function findByOfficeIdAndDate(Office $office, CarbonInterface $date, Plan $plan): Collection;
}
