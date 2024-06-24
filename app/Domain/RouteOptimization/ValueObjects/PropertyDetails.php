<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\ValueObjects;

class PropertyDetails
{
    public function __construct(
        private readonly float $landSqFt,
        private readonly float $buildingSqFt,
        private readonly float $livingSqFt,
    ) {
    }

    /**
     * Get the land square footage.
     *
     * @return float
     */
    public function getLandSqFt(): float
    {
        return $this->landSqFt;
    }

    /**
     * Get the building square footage.
     *
     * @return float
     */
    public function getBuildingSqFt(): float
    {
        return $this->buildingSqFt;
    }

    /**
     * Get the living square footage.
     *
     * @return float
     */
    public function getLivingSqFt(): float
    {
        return $this->livingSqFt;
    }
}
