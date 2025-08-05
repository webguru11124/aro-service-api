<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\DTO;

use Carbon\CarbonInterface;

readonly class VroomTimeWindow implements VroomArrayFormat
{
    public function __construct(
        private CarbonInterface $start,
        private CarbonInterface $end
    ) {
    }

    /**
     * @return int[]
     */
    public function toArray(): array
    {
        return [
            $this->start->timestamp,
            $this->end->timestamp,
        ];
    }
}
