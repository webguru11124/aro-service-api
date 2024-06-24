<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\ValueObjects\RouteMetrics;

use App\Domain\RouteOptimization\Enums\MetricKey;

class Average
{
    public function __construct(
        private MetricKey $key,
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
     * Get the score for the metric
     */
    public function getScore(): Score
    {
        return $this->score;
    }
}
