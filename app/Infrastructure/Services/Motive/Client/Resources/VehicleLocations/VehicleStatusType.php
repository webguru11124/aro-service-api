<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\VehicleLocations;

enum VehicleStatusType: string
{
    case ACTIVE = 'active';
    case DEACTIVATED = 'deactivated';
}
