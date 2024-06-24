<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Entities;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Carbon\CarbonInterface;

class Appointment
{
    public function __construct(
        private int $id,
        private bool $initial,
        private CarbonInterface $date,
        private CarbonInterface|null $dateCompleted,
        private Customer $customer,
        private Duration $duration,
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
     * @return bool
     */
    public function isInitial(): bool
    {
        return $this->initial;
    }

    /**
     * @return CarbonInterface
     */
    public function getDate(): CarbonInterface
    {
        return $this->date;
    }

    /**
     * @return CarbonInterface|null
     */
    public function getDateCompleted(): CarbonInterface|null
    {
        return $this->dateCompleted;
    }

    /**
     * @return Coordinate
     */
    public function getLocation(): Coordinate
    {
        return $this->getCustomer()->getLocation();
    }

    /**
     * @return Customer
     */
    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    /**
     * @return Duration
     */
    public function getDuration(): Duration
    {
        return $this->duration;
    }
}
