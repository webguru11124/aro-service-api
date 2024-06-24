<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Services\Actions;

use App\Domain\SharedKernel\Entities\Office;
use Carbon\CarbonInterface;

interface ReserveTimeForCalendarEvents
{
    /**
     * @param Office $office
     * @param CarbonInterface $date
     *
     * @return void
     */
    public function execute(Office $office, CarbonInterface $date): void;
}
