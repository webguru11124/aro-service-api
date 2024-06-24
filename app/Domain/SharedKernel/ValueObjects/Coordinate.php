<?php

declare(strict_types=1);

namespace App\Domain\SharedKernel\ValueObjects;

use Illuminate\Contracts\Support\Jsonable;

readonly class Coordinate implements Jsonable
{
    private const EARTH_RADIUS = 3959.87433; //miles

    public function __construct(
        private float $latitude,
        private float $longitude
    ) {
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    /**
     * @param $options
     *
     * @return false|string
     */
    public function toJson($options = 0): false|string
    {
        return json_encode([
            'lat' => $this->latitude,
            'lon' => $this->longitude,
        ], $options);
    }

    /**
     * Calculates distance to another geo-location using of the Haversini formula
     *
     * @param Coordinate $target
     *
     * @return Distance
     */
    public function distanceTo(Coordinate $target): Distance
    {
        $dLat = deg2rad($target->latitude - $this->getLatitude());
        $dLon = deg2rad($target->longitude - $this->getLongitude());

        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($this->getLatitude())) * cos(deg2rad($target->latitude)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * self::EARTH_RADIUS * asin(sqrt($a));

        return Distance::fromMiles($c);
    }
}
