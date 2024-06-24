<?php

declare(strict_types=1);

namespace App\Application\DTO;

use Carbon\CarbonInterface;

readonly class ScoreNotificationsDTO
{
    /**
     * @param int[] $officeIds
     * @param CarbonInterface|null $date
     */
    public function __construct(
        public array $officeIds,
        public CarbonInterface|null $date,
    ) {
    }
}
