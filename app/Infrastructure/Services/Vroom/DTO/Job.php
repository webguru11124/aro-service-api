<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\DTO;

class Job implements VroomArrayFormat
{
    /** @var VroomTimeWindow[] */
    private array $timeWindows;

    public function __construct(
        private int $id, // Appointment ID
        private string $description, // the name of the service type for the appointment
        private Skills $skills,
        private int $service, // Job service duration in seconds
        private Delivery $delivery,
        private VroomCoordinate $location,
        private int $priority, // Integer from 0 to 100 describing priority level
        private int $setup, // Job setup duration in seconds
        VroomTimeWindow ...$timeWindows,
    ) {
        $this->timeWindows = array_values($timeWindows);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'time_windows' => array_map(fn (VroomTimeWindow $timeWindow) => $timeWindow->toArray(), $this->timeWindows),
            'skills' => $this->skills->toArray(),
            'service' => $this->service,
            'location' => $this->location->toArray(),
            'description' => $this->description,
            'priority' => $this->priority,
            'delivery' => $this->delivery->toArray(),
            'setup' => $this->setup,
        ];
    }
}
