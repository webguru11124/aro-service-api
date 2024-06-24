<?php

declare(strict_types=1);

namespace App\Domain\Tracking\ValueObjects;

use App\Domain\SharedKernel\ValueObjects\Duration;

readonly class RouteCompletionStats
{
    public function __construct(
        private float|null $routeAdherence,
        private int $totalAppointments,
        private Duration $totalServiceTime,
        private bool $atRisk = false,
        private float $completionPercentage = 0,
    ) {
    }

    /**
     * @return float|null
     */
    public function getRouteAdherence(): float|null
    {
        return $this->routeAdherence;
    }

    /**
     * @return int
     */
    public function getTotalAppointments(): int
    {
        return $this->totalAppointments;
    }

    /**
     * @return Duration
     */
    public function getTotalServiceTime(): Duration
    {
        return $this->totalServiceTime;
    }

    /**
     * @return bool
     */
    public function isAtRisk(): bool
    {
        return $this->atRisk;
    }

    /**
     * @return float
     */
    public function getCompletionPercentage(): float
    {
        return $this->completionPercentage;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total_appointments' => $this->getTotalAppointments(),
            'total_service_time_minutes' => $this->getTotalServiceTime()->getTotalMinutes(),
            'route_adherence' => $this->getRouteAdherence(),
            'completion_percentage' => $this->getCompletionPercentage(),
        ];
    }
}
