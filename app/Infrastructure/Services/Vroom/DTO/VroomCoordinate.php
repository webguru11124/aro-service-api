<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\DTO;

readonly class VroomCoordinate implements VroomArrayFormat
{
    public function __construct(
        private float $latitude,
        private float $longitude
    ) {
    }

    /**
     * @return float[]
     */
    public function toArray(): array
    {
        // Vroom expects the order of coordinate arrays to be [lon, lat]
        return [
            $this->longitude,
            $this->latitude,
        ];
    }
}
