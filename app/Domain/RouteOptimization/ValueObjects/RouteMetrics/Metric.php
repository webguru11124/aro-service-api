<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\ValueObjects\RouteMetrics;

use App\Domain\RouteOptimization\Enums\MetricKey;

class Metric
{
    public function __construct(
        private MetricKey $key,
        private mixed $value,
        private Weight $weight,
        private Score $score,
    ) {
    }

    /**
     * Get a human readable name for the metric
     */
    public function getName(): string
    {
        return ucwords(implode(' ', explode('_', $this->key->value)));
    }

    /**
     * Get the key (unique identification string) for the metric
     */
    public function getKey(): MetricKey
    {
        return $this->key;
    }

    /**
     * Get the weight for the metric
     */
    public function getWeight(): Weight
    {
        return $this->weight;
    }

    /**
     * Get the score for the metric
     */
    public function getScore(): Score
    {
        return $this->score;
    }

    /**
     * Get the weighted score for the metric
     */
    public function getWeightedScore(): float
    {
        return $this->weight->value() * $this->score->value();
    }

    /**
     * Get the maximum possible weighted score for the metric
     */
    public function getMaxPossibleWeightedScore(): float
    {
        return $this->weight->value() * $this->score->getMaxPossibleScore();
    }

    /**
     * Get metric value
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }
}
