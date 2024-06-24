<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Entities;

use App\Domain\RouteOptimization\Enums\ServiceType;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Carbon\CarbonInterface;

class ServiceHistory
{
    public function __construct(
        private readonly int $id,
        private readonly int $customerId,
        private readonly ServiceType $serviceType,
        private readonly Duration $duration,
        private readonly CarbonInterface $date
    ) {
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    /**
     * @return Duration
     */
    public function getDuration(): Duration
    {
        return $this->duration;
    }

    /**
     * @return CarbonInterface
     */
    public function getDate(): CarbonInterface
    {
        return $this->date;
    }

    /**
     * @return bool
     */
    public function isInitial(): bool
    {
        return $this->serviceType === ServiceType::INITIAL;
    }

    /**
     * @return int
     */
    public function getQuarter(): int
    {
        return $this->date->quarter;
    }
}
