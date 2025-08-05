<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repositories;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Exceptions\OptimizationStateNotFoundException;

interface OptimizationStateRepository
{
    /**
     * Persist an optimization to a data store. Update or Create
     *
     * @param OptimizationState $state
     *
     * @return void
     */
    public function save(OptimizationState $state): void;

    /**
     * Get the next ID to use for the entity
     */
    public function getNextId(): int;

    /**
     * Find a serialized optimization state by its ID
     *
     * @param int $stateId
     *
     * @return OptimizationState
     * @throws OptimizationStateNotFoundException
     */
    public function findById(int $stateId): OptimizationState;
}
