<?php

declare(strict_types=1);

namespace App\Domain\Tracking\ValueObjects;

use Carbon\CarbonInterface;

readonly class TreatmentStateIdentity
{
    public function __construct(
        public int $officeId,
        public CarbonInterface $date,
    ) {
    }
}
