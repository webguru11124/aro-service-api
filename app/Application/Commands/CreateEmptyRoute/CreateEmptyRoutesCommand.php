<?php

declare(strict_types=1);

namespace App\Application\Commands\CreateEmptyRoute;

use App\Domain\SharedKernel\Entities\Office;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class CreateEmptyRoutesCommand
{
    public function __construct(
        public readonly Office $office,
        public readonly CarbonInterface $date,
        public readonly Collection $employees,
    ) {
    }
}
