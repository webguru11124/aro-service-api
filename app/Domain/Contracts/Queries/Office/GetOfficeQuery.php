<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Queries\Office;

use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\OfficeNotFoundException;

interface GetOfficeQuery
{
    /**
     * Get an office by its id.
     *
     * @param int $officeId
     *
     * @return Office
     * @throws OfficeNotFoundException
     */
    public function get(int $officeId): Office;
}
