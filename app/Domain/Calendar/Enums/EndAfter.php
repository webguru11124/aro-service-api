<?php

declare(strict_types=1);

namespace App\Domain\Calendar\Enums;

enum EndAfter: string
{
    case DATE = 'date';
    case OCCURRENCES = 'occurrences';
    case NEVER = 'never';
}
