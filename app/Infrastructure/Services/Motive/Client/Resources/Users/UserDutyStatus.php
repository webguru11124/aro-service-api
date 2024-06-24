<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources\Users;

enum UserDutyStatus: string
{
    case OFF_DUTY = 'off_duty';
    case ON_DUTY = 'on_duty';
    case SLEEPER = 'sleeper';
    case DRIVING = 'driving';
    case WAITING = 'waiting';
}
