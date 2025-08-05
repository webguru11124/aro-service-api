<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\Enums;

enum SpotType
{
    case REGULAR;
    case BUCKET;
    case ARO_BLOCKED;
}
