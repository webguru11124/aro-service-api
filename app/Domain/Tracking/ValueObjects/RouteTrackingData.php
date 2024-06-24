<?php

declare(strict_types=1);

namespace App\Domain\Tracking\ValueObjects;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use Carbon\CarbonInterface;

readonly class RouteTrackingData
{
    public function __construct(
        private string $id,
        private Coordinate|null $driverLocation,
        private CarbonInterface|null $driverLocatedAt,
        private Coordinate|null $vehicleLocation,
        private CarbonInterface|null $vehicleLocatedAt,
        private float|null $vehicleSpeed,
    ) {
    }

    /**
     * @return Coordinate|null
     */
    public function getDriverLocation(): Coordinate|null
    {
        return $this->driverLocation;
    }

    /**
     * @return CarbonInterface|null
     */
    public function getDriverLocatedAt(): CarbonInterface|null
    {
        return $this->driverLocatedAt;
    }

    /**
     * @return Coordinate|null
     */
    public function getVehicleLocation(): Coordinate|null
    {
        return $this->vehicleLocation;
    }

    /**
     * @return CarbonInterface|null
     */
    public function getVehicleLocatedAt(): CarbonInterface|null
    {
        return $this->vehicleLocatedAt;
    }

    /**
     * @return float|null
     */
    public function getVehicleSpeed(): float|null
    {
        return $this->vehicleSpeed;
    }

    /**
     * Formats tracking data as an array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'driver_location' => !empty($this->getDriverLocation())
                ? $this->buildLocation($this->getDriverLocation(), $this->getDriverLocatedAt())
                : null,
            'vehicle_location' => !empty($this->getVehicleLocation())
                ? $this->buildLocation($this->getVehicleLocation(), $this->getVehicleLocatedAt())
                : null,
            'vehicle_speed' => $this->getVehicleSpeed(),
        ];
    }

    /**
     * @param Coordinate $location
     * @param CarbonInterface $timestamp
     *
     * @return array<string, mixed>
     */
    private function buildLocation(Coordinate $location, CarbonInterface $timestamp): array
    {
        return [
            'lat' => $location->getLatitude(),
            'lng' => $location->getLongitude(),
            'timestamp' => $timestamp->toISOString(),
        ];
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
}
