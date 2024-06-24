<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Entities;

trait HasRouteId
{
    private int|null $routeId = null;

    /**
     * @return int|null
     */
    public function getRouteId(): int|null
    {
        return $this->routeId;
    }

    /**
     * @param int $routeId
     *
     * @return $this
     */
    public function setRouteId(int $routeId): self
    {
        $this->routeId = $routeId;

        return $this;
    }
}
