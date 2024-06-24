<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repositories;

use App\Domain\SharedKernel\Entities\Office;
use App\Domain\Tracking\Entities\ServicedRoute;
use App\Infrastructure\Exceptions\NoRegularRoutesFoundException;
use App\Infrastructure\Exceptions\NoServiceProFoundException;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface ServicedRoutesRepository
{
    /**
     * Returns serviced routes for specified office and date
     *
     * @param Office $office
     * @param CarbonInterface $date
     *
     * @return Collection<ServicedRoute>
     * @throws NoRegularRoutesFoundException
     * @throws NoServiceProFoundException
     */
    public function findByOfficeAndDate(Office $office, CarbonInterface $date): Collection;
}
