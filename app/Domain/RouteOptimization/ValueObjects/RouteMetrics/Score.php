<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\ValueObjects\RouteMetrics;

class Score
{
    public const MAX_POSSIBLE_SCORE = 5;
    public const MIN_POSSIBLE_SCORE = 0;
    private const SCORE_PRECISION = 2;

    private float|int $value;

    public function __construct(
        float|int $value
    ) {
        $this->validate($value);
        $this->value = round($value, self::SCORE_PRECISION);
    }

    /**
     * Validates that a score cannot be less than 0 or greater than 5
     */
    private function validate(float|int $value): void
    {
        if ($value < self::MIN_POSSIBLE_SCORE) {
            throw new \InvalidArgumentException("The value of a Score cannot be less than 0. Value given: $value");
        }

        if ($value > self::MAX_POSSIBLE_SCORE) {
            throw new \InvalidArgumentException("The value of a Score cannot be more than 5. Value given: $value");
        }
    }

    /**
     * Returns the score rounded to precision of 2
     */
    public function value(): float|int
    {
        return $this->value;
    }

    public function getMaxPossibleScore(): float|int
    {
        return self::MAX_POSSIBLE_SCORE;
    }

    public function getMinPossibleScore(): float|int
    {
        return self::MIN_POSSIBLE_SCORE;
    }
}
