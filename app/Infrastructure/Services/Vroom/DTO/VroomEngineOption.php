<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\DTO;

enum VroomEngineOption: string
{
    case CHOOSE_ETA = 'c'; // choose ETA for custom routes and report violations
    case GEOMETRY = 'g'; // add detailed route geometry and distance
}
