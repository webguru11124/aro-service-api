<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\ValueObjects\Weather;

readonly class Temperature
{
    public function __construct(
        public float|null $temp,
        public float|null $min,
        public float|null $max
    ) {
    }
}
