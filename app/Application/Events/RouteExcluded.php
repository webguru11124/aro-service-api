<?php

declare(strict_types=1);

namespace App\Application\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Domain\SharedKernel\Entities\Office;
use Carbon\CarbonInterface;

class RouteExcluded
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param int[] $routeIds
     */
    public function __construct(
        public array $routeIds,
        public Office $office,
        public CarbonInterface $date,
        public string $reason
    ) {
    }
}
