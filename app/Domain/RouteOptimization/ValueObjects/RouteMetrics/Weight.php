<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\ValueObjects\RouteMetrics;

class Weight
{
    private const MAX_POSSIBLE_WEIGHT = 1;
    private const MIN_POSSIBLE_WEIGHT = 0;

    private float $value;

    public function __construct(float $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    private function validate(float $value): void
    {
        if ($value < self::MIN_POSSIBLE_WEIGHT) {
            throw new \InvalidArgumentException("The value of a Weight cannot be less than 0. Value given: $value");
        }

        if ($value > self::MAX_POSSIBLE_WEIGHT) {
            throw new \InvalidArgumentException("The value of a Weight cannot be greater than 1. Value given: $value");
        }
    }

    /**
     * Returns weight value
     *
     * @return float
     */
    public function value(): float
    {
        return $this->value;
    }
}
