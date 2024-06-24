<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repositories;

use App\Domain\Scheduling\Entities\PendingService;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\NoTechFoundException;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface RescheduledPendingServiceRepository
{
    /**
     * Returns collection of pending services that are on reschedule routes for office and date
     *
     * @param Office $office
     * @param CarbonInterface $date
     *
     * @return Collection<PendingService>
     * @throws NoTechFoundException
     */
    public function findByOfficeIdAndDate(Office $office, CarbonInterface $date): Collection;
}
