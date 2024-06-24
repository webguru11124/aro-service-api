<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Queries;

use Illuminate\Support\Collection;

interface HistoricalAppointmentsQuery
{
    /**
     * Fetches completed appointments for specific customers within the last 24 months,
     * grouped by customer ID.
     *
     * @param array<int> $customerIds Array of customer IDs to filter the appointments.
     * @param int $officeId The office ID to filter the appointments.
     *
     * @return Collection
     */
    public function find($customerIds, $officeId): Collection;
}
