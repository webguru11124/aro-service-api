<?php

declare(strict_types=1);

namespace App\Domain\Tracking\Entities;

use App\Domain\SharedKernel\Entities\Customer;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\CarbonInterface;

class ScheduledAppointment
{
    public function __construct(
        private int $id,
        private CarbonInterface $date,
        private TimeWindow|null $serviceTimeWindow,
        private TimeWindow|null $expectedTimeWindow,
        private CarbonInterface|null $dateComplete,
        private Customer $customer,
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
     * @return CarbonInterface
     */
    public function getDate(): CarbonInterface
    {
        return $this->date;
    }

    /**
     * It returns actual service time window
     *
     * @return TimeWindow|null
     */
    public function getServiceTimeWindow(): TimeWindow|null
    {
        return $this->serviceTimeWindow;
    }

    /**
     * It returns the expected time window of appointment based on optimization results
     *
     * @return TimeWindow|null
     */
    public function getExpectedTimeWindow(): TimeWindow|null
    {
        return $this->expectedTimeWindow;
    }

    /**
     * @return CarbonInterface|null
     */
    public function getDateComplete(): CarbonInterface|null
    {
        return $this->dateComplete;
    }

    /**
     * @return Customer
     */
    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    /**
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->dateComplete !== null;
    }

    /**
     * @return Coordinate
     */
    public function getLocation(): Coordinate
    {
        return $this->getCustomer()->getLocation();
    }
}
