<?php

declare(strict_types=1);

namespace App\Application\Commands\ResetPreferredTech;

use Illuminate\Support\Collection;

class ResetPreferredTechCommand
{
    public function __construct(
        public readonly Collection $resignedTechAssignments,
        public readonly int $officeId,
    ) {
    }
}
