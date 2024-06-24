<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repositories;

use App\Domain\Tracking\Entities\TreatmentState;

interface TreatmentStateRepository
{
    /**
     * Updates treatment state data
     *
     * @param TreatmentState $state
     *
     * @return void
     */
    public function save(TreatmentState $state): void;
}
