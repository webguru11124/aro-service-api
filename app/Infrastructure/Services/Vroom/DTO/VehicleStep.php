<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\DTO;

use App\Infrastructure\Services\Vroom\Enums\StepType;

class VehicleStep implements VroomArrayFormat
{
    public function __construct(
        private StepType $type,
        private int $id,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'id' => $this->id,
        ];
    }
}
