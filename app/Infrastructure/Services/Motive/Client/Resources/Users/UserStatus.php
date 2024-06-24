<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\Users;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case DEACTIVATED = 'deactivated';
}
