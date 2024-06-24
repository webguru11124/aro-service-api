<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\ValueObjects;

readonly class Triangle
{
    public function __construct(
        public int $a,
        public int $b,
        public int $c,
    ) {
    }
}
