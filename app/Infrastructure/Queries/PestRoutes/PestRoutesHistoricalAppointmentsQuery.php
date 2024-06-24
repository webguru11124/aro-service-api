<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\PestRoutes;

use App\Domain\Contracts\Queries\HistoricalAppointmentsQuery;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\CachableAppointmentsDataProcessor;
use Illuminate\Support\Collection;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Aptive\PestRoutesSDK\Filters\DateFilter;
use Carbon\Carbon;

class PestRoutesHistoricalAppointmentsQuery implements HistoricalAppointmentsQuery
{
    private const HISTORICAL_PERIOD_MONTHS = 24;

    public function __construct(
        private readonly CachableAppointmentsDataProcessor $appointmentsDataProcessor,
    ) {
    }

    /**
     * Fetches completed appointments for specific customers within the last 24 months,
     * grouped by customer ID.
     *
     * @param array<int> $customerIds Array of customer IDs to filter the appointments.
     * @param int $officeId The office ID to filter the appointments.
     *
     * @return Collection
     */
    public function find($customerIds, $officeId): Collection
    {
        $historicalStartDate = Carbon::now()->subMonths(self::HISTORICAL_PERIOD_MONTHS);

        $pestRoutesAppointments = $this->appointmentsDataProcessor->extract($officeId, new SearchAppointmentsParams(
            officeIds: [$officeId],
            status: AppointmentStatus::Completed,
            customerIds: $customerIds,
            date: DateFilter::greaterThanOrEqualTo($historicalStartDate)
        ));

        return $pestRoutesAppointments->groupBy('customerId');
    }
}
