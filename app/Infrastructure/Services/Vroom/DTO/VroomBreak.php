<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\DTO;

class VroomBreak implements VroomArrayFormat
{
    /** @var VroomTimeWindow[] */
    private array $timeWindows;

    public function __construct(
        private int $id,
        private string $description, // Service Pro Full Name
        private int $service, // Break Duration defaults to 0
        private int|null $maxLoad, //maximum vehicle load for which this break can happen
        VroomTimeWindow ...$timeWindows,
    ) {
        $this->timeWindows = $timeWindows;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'id' => $this->id,
            'description' => $this->description,
            'service' => $this->service,
            'time_windows' => array_map(fn (VroomTimeWindow $timeWindow) => $timeWindow->toArray(), $this->timeWindows),
        ];

        if ((int) $this->maxLoad > 0) {
            $result['max_load'] = [$this->maxLoad];
        }

        return $result;
    }
}
