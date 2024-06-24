<?php

declare(strict_types=1);

namespace App\Application\Managers;

use App\Application\DTO\ServiceStatsDTO;
use App\Application\Jobs\ServiceStatsJob;
use App\Domain\Contracts\Queries\Office\GetOfficesByIdsQuery;
use App\Domain\SharedKernel\Entities\Office;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ServiceStatsManager
{
    /** @var Collection<Office> */
    private Collection $offices;

    public function __construct(
        private readonly GetOfficesByIdsQuery $officesByIdsQuery,
    ) {
    }

    /**
     * @param ServiceStatsDTO $dto
     */
    public function manage(ServiceStatsDTO $dto): void
    {
        $this->offices = $this->officesByIdsQuery->get($dto->officeIds);

        /** @var Office $office */
        foreach ($this->offices as $office) {
            $dateInOfficeTimezone = is_null($dto->date)
                ? Carbon::today($office->getTimezone())
                : Carbon::parse($dto->date->toDateString(), $office->getTimezone());

            ServiceStatsJob::dispatch($dateInOfficeTimezone, $office);
            Log::info(__('messages.office.gathering_stats_initiated', [
                'office' => $office->getName(),
                'office_id' => $office->getId(),
                'date' => $dateInOfficeTimezone->toDateString(),
            ]));
        }
    }
}
