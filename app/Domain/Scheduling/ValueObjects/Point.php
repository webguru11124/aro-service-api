<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\ValueObjects;

readonly class Point
{
    public function __construct(
        public float $x,
        public float $y,
    ) {
    }
}
