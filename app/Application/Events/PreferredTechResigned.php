<?php

declare(strict_types=1);

namespace App\Application\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use App\Domain\Scheduling\ValueObjects\ResignedTechAssignment;

class PreferredTechResigned
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param Collection<ResignedTechAssignment> $resignedTechAssignments
     * @param int $officeId
     */
    public function __construct(
        private Collection $resignedTechAssignments,
        private int $officeId,
    ) {
    }

    /**
     * @return Collection<ResignedTechAssignment>
     */
    public function getResignedTechAssignments(): Collection
    {
        return $this->resignedTechAssignments;
    }

    /**
     * @return int
     */
    public function getOfficeId(): int
    {
        return $this->officeId;
    }
}
