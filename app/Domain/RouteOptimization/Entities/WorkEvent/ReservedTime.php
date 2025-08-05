<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Entities\WorkEvent;

use App\Domain\RouteOptimization\Entities\HasRouteId;
use App\Domain\RouteOptimization\Enums\WorkEventType;

class ReservedTime extends AbstractWorkEvent
{
    use HasRouteId;

    /**
     * @return WorkEventType
     */
    public function getType(): WorkEventType
    {
        return WorkEventType::RESERVED_TIME;
    }
}
