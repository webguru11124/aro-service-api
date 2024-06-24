<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repositories;

use App\Domain\Scheduling\Entities\ScheduledRoute;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\NoRegularRoutesFoundException;
use App\Infrastructure\Exceptions\NoServiceProFoundException;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface ScheduledRouteRepository
{
    /**
     * Persists scheduled route
     *
     * @param ScheduledRoute $scheduledRoute
     *
     * @return void
     */
    public function save(ScheduledRoute $scheduledRoute): void;

    /**
     * Returns collection of scheduled routes
     *
     * @param Office $office
     * @param CarbonInterface $date
     *
     * @return Collection<ScheduledRoute>
     * @throws NoRegularRoutesFoundException
     * @throws NoServiceProFoundException
     * @throws InternalServerErrorHttpException
     */
    public function findByOfficeIdAndDate(Office $office, CarbonInterface $date): Collection;
}
