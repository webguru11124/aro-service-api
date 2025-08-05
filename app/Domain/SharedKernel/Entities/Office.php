<?php

declare(strict_types=1);

namespace App\Domain\SharedKernel\Entities;

use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\Timezone;
use Carbon\CarbonTimeZone;

class Office
{
    private const SERVICABLE_AREA_RADIUS_MILES = 100;

    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $region,
        private readonly Address $address,
        private readonly CarbonTimeZone $timezone,
        private readonly Coordinate $location,
    ) {
    }

    /**
     * Get the value of id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the value of name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the value of region
     *
     * @return string
     */
    public function getRegion(): string
    {
        return $this->region;
    }

    /**
     * Get the value of address
     *
     * @return string|null
     */
    public function getAddress(): string|null
    {
        return $this->address->getAddress();
    }

    /**
     * Get the value of city
     *
     * @return string|null
     */
    public function getCity(): string|null
    {
        return $this->address->getCity();
    }

    /**
     * Get the value of state
     *
     * @return string|null
     */
    public function getState(): string|null
    {
        return $this->address->getState();
    }

    /**
     * Get the value of timezone
     *
     * @return CarbonTimeZone
     */
    public function getTimezone(): CarbonTimeZone
    {
        return $this->timezone;
    }

    /**
     * Get the full name of the timezone
     *
     * @return string
     */
    public function getTimezoneFullName(): string
    {
        return (new Timezone($this->timezone->getName()))->getTimezoneFullName();
    }

    /**
     * Get the value of location
     *
     * @return Coordinate
     */
    public function getLocation(): Coordinate
    {
        return $this->location;
    }

    /**
     * It returns true of location in the serviceable area
     *
     * @param Coordinate $location
     *
     * @return bool
     */
    public function isLocationInServiceableArea(Coordinate $location): bool
    {
        return $this->getLocation()->distanceTo($location)->getMiles() <= self::SERVICABLE_AREA_RADIUS_MILES;
    }
}
