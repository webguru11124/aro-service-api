<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\DTO;

use Illuminate\Support\Collection;

class Vehicle implements VroomArrayFormat
{
    public const DEFAULT_SPEED_FACTOR = 1.00;

    /** @var Collection<VroomBreak> */
    private Collection $breaks;

    /** @var Collection<VehicleStep> */
    private Collection $steps;

    public function __construct(
        private int $id, // Service Pro id
        private string $description, // Service Pro Full Name
        private Skills $skills,
        private VroomCoordinate $startLocation,
        private VroomCoordinate $endLocation,
        private VroomTimeWindow $timeWindow,
        private Capacity $capacity,
        private float $speedFactor = self::DEFAULT_SPEED_FACTOR,
    ) {
        $this->breaks = new Collection();
        $this->steps = new Collection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param VroomBreak $break
     *
     * @return $this
     */
    public function addBreak(VroomBreak $break): self
    {
        $this->breaks->add($break);

        return $this;
    }

    /**
     * @param VehicleStep $step
     *
     * @return $this
     */
    public function addStep(VehicleStep $step): self
    {
        $this->steps->add($step);

        return $this;
    }

    /**
     * @param float $speedFactor
     *
     * @return $this
     */
    public function setSpeedFactor(float $speedFactor): self
    {
        $this->speedFactor = $speedFactor;

        return $this;
    }

    /**
     * @return float
     */
    public function getSpeedFactor(): float
    {
        return $this->speedFactor;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'time_window' => $this->timeWindow->toArray(),
            'capacity' => $this->capacity->toArray(),
            'skills' => $this->skills->toArray(),
            'start' => $this->startLocation->toArray(),
            'end' => $this->endLocation->toArray(),
            'breaks' => $this->breaks->map(fn (VroomBreak $break) => $break->toArray())->toArray(),
            'speed_factor' => $this->speedFactor,
            'steps' => $this->steps->map(fn (VehicleStep $step) => $step->toArray())->toArray(),
        ];
    }
}
