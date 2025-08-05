<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repositories;

use App\Domain\Scheduling\Entities\SchedulingState;

interface SchedulingStateRepository
{
    /**
     * Persists scheduling state
     *
     * @param SchedulingState $schedulingState
     *
     * @return void
     */
    public function save(SchedulingState $schedulingState): void;

    /**
     * Get the next ID to use for the entity
     */
    public function getNextId(): int;
}
