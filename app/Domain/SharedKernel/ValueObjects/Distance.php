<?php

declare(strict_types=1);

namespace App\Domain\SharedKernel\ValueObjects;

class Distance
{
    private const FLOAT_PRECISION = 2;
    private const METERS_IN_MILE = 1609.34;

    private function __construct(
        private readonly float $meters
    ) {
    }

    /**
     * @param float $meters
     *
     * @return self
     */
    public static function fromMeters(float $meters): self
    {
        return new self($meters);
    }

    /**
     * @param float $kilometers
     *
     * @return self
     */
    public static function fromKilometers(float $kilometers): self
    {
        return new self($kilometers * 1000);
    }

    /**
     * @param float $miles
     *
     * @return self
     */
    public static function fromMiles(float $miles): self
    {
        $meters = $miles * self::METERS_IN_MILE;

        return new self($meters);
    }

    /**
     * @return float
     */
    public function getMeters(): float
    {
        return round($this->meters, self::FLOAT_PRECISION);
    }

    /**
     * @return float
     */
    public function getMiles(): float
    {
        return round($this->meters / self::METERS_IN_MILE, self::FLOAT_PRECISION);
    }

    /**
     * @return int
     */
    public function getIntMeters(): int
    {
        return (int) floor($this->meters);
    }

    /**
     * @return float
     */
    public function getKilometers(): float
    {
        return round($this->meters / 1000, self::FLOAT_PRECISION);
    }
}
