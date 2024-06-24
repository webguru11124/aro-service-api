<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\ValueObjects;

use App\Domain\RouteOptimization\Enums\WorkEventType;

readonly class StartLocation extends LocationEvent
{
    public function getType(): WorkEventType
    {
        return WorkEventType::START_LOCATION;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Start';
    }
}
