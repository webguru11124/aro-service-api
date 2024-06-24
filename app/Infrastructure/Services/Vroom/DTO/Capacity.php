<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\DTO;

class Capacity implements VroomArrayFormat
{
    /**
     * @param int[] $amounts
     */
    public function __construct(
        private array $amounts = []
    ) {
    }

    /**
     * @return int[]
     */
    public function toArray(): array
    {
        return $this->amounts;
    }
}
