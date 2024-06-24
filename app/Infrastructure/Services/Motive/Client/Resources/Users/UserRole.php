<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\Users;

enum UserRole: string
{
    case ADMIN = 'admin';
    case DRIVER = 'driver';
    case FLEET = 'fleet_user';
}
