<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Queries;

use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\RouteTemplatesNotFoundException;

interface GetRouteTemplateQuery
{
    /**
     * Get applicable route template id for specified office.
     *
     * @param Office $office
     *
     * @return int
     * @throws RouteTemplatesNotFoundException
     */
    public function get(Office $office): int;
}
