<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\Enums;

enum StepType: string
{
    case BREAK = 'break';
    case END = 'end';
    case START = 'start';
    case JOB = 'job';
}
