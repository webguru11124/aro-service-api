<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\ValueObjects;

class RouteConfig
{
    private const int DEFAULT_BREAKS = 3;

    public function __construct(
        private int $insideSales = 0,
        private int $summary = 0,
        private int $breaks = self::DEFAULT_BREAKS,
    ) {
    }

    /**
     * @param int $num
     *
     * @return RouteConfig
     */
    public function setInsideSales(int $num): RouteConfig
    {
        return new self(
            $num,
            $this->summary,
            $this->breaks,
        );
    }

    /**
     * @return RouteConfig
     */
    public function enableRouteSummary(): RouteConfig
    {
        return new self(
            $this->insideSales,
            1,
            $this->breaks,
        );
    }

    /**
     * @return int
     */
    public function getInsideSales(): int
    {
        return $this->insideSales;
    }

    /**
     * @return int
     */
    public function getSummary(): int
    {
        return $this->summary;
    }

    /**
     * @return int
     */
    public function getBreaks(): int
    {
        return $this->breaks;
    }
}
