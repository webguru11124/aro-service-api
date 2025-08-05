<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\ValueObjects\Weather;

use Carbon\CarbonInterface;

readonly class WeatherInfoIdentity
{
    public function __construct(
        private int $officeId,
        private CarbonInterface $date,
    ) {
    }

    /**
     * @return CarbonInterface
     */
    public function getDate(): CarbonInterface
    {
        return $this->date;
    }

    /**
     * @return int
     */
    public function getOfficeId(): int
    {
        return $this->officeId;
    }

    /**
     * Creates instance of the class with given parameters
     *
     * @param int $officeId
     * @param CarbonInterface $date
     *
     * @return self
     */
    public static function create(int $officeId, CarbonInterface $date): self
    {
        return new self($officeId, $date);
    }
}
