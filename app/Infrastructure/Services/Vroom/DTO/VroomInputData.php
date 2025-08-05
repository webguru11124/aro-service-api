<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\DTO;

use Illuminate\Support\Collection;

readonly class VroomInputData implements VroomArrayFormat
{
    /**
     * @param Collection<int, Vehicle> $vehiclesCollection
     * @param Collection<int, Job> $jobsCollection
     * @param Collection<VroomEngineOption> $options
     */
    public function __construct(
        private Collection $vehiclesCollection,
        private Collection $jobsCollection,
        private Collection $options
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $optionsArray = $this->options->map(fn (VroomEngineOption $option) => $option->value)->toArray();

        return [
            'vehicles' => $this->vehiclesCollection->map(fn (Vehicle $vehicle) => $vehicle->toArray())->toArray(),
            'jobs' => $this->jobsCollection->map(fn (Job $job) => $job->toArray())->toArray(),
            'options' => array_fill_keys($optionsArray, true),
        ];
    }

    /**
     * @return Collection<Vehicle>
     */
    public function getVehicles(): Collection
    {
        return $this->vehiclesCollection;
    }

    /**
     * @return Collection<Job>
     */
    public function getJobs(): Collection
    {
        return $this->jobsCollection;
    }

    /**
     * @return Collection<VroomEngineOption>
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }
}
