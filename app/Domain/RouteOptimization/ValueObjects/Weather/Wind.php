<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\ValueObjects\Weather;

readonly class Wind
{
    public function __construct(
        public float|null $speed,
        public string|null $direction,
    ) {
    }
}
