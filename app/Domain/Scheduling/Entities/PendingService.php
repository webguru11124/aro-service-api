<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Entities;

use App\Domain\Scheduling\ValueObjects\CustomerPreferences;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\CarbonInterface;

class PendingService
{
    private const MAX_PRIORITY = 100;
    private const MIN_PRIORITY = 1;

    public function __construct(
        private int $subscriptionId,
        private Plan $plan,
        private Customer $customer,
        private Appointment $previousAppointment,
        private CarbonInterface $nextServiceDate,
        private CustomerPreferences $customerPreferences,
        private Appointment|null $nextAppointment = null,
    ) {
    }

    /**
     * @return int
     */
    public function getSubscriptionId(): int
    {
        return $this->subscriptionId;
    }

    /**
     * @return Plan
     */
    public function getPlan(): Plan
    {
        return $this->plan;
    }

    /**
     * @return Customer
     */
    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    /**
     * @return Appointment
     */
    public function getPreviousAppointment(): Appointment
    {
        return $this->previousAppointment;
    }

    /**
     * @return string
     */
    public function getPreferredStart(): string
    {
        return $this->customerPreferences->getPreferredStart();
    }

    /**
     * @return string
     */
    public function getPreferredEnd(): string
    {
        return $this->customerPreferences->getPreferredEnd();
    }

    /**
     * @return int|null
     */
    public function getPreferredEmployeeId(): int|null
    {
        return $this->customerPreferences->getPreferredEmployeeId();
    }

    /**
     * Reset the preferred employee id of the pending service.
     *
     * @return self
     */
    public function resetPreferredEmployeeId(): self
    {
        $this->customer->resetPreferredTechId();
        $this->customerPreferences = $this->customerPreferences->resetPreferredEmployeeId();

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPreferredDay(): int|null
    {
        return $this->customerPreferences->getPreferredDay();
    }

    /**
     * @return CarbonInterface
     */
    public function getNextServiceDate(): CarbonInterface
    {
        return $this->nextServiceDate;
    }

    /**
     * @return bool
     */
    public function isHighPriority(): bool
    {
        return $this->getNextServiceDate()->greaterThanOrEqualTo($this->getNextServiceTimeWindow()->getEndAt());
    }

    /**
     * @return int
     */
    public function getServiceTypeId(): int
    {
        return $this->getPlan()->getServiceTypeId();
    }

    /**
     * @return Coordinate
     */
    public function getLocation(): Coordinate
    {
        return $this->getCustomer()->getLocation();
    }

    /**
     * @return TimeWindow
     */
    public function getNextServiceTimeWindow(): TimeWindow
    {
        $date = $this->getPreviousAppointment()->getDateCompleted() ?? $this->getPreviousAppointment()->getDate();

        $serviceIntervalDays = $this->getPreviousAppointment()->isInitial()
            ? $this->plan->getInitialFollowUpDays()
            : $this->plan->getServiceIntervalDays($date);

        $servicePeriodDays = $this->getServicePeriodDays($date);

        $startDate = $date->clone()->addDays($serviceIntervalDays);
        $endDate = $startDate->clone()->addDays($servicePeriodDays);

        return new TimeWindow($startDate, $endDate);
    }

    private function getServicePeriodDays(CarbonInterface $date): int
    {
        return $this->getPreviousAppointment()->isInitial()
            ? $this->plan->getInitialServicePeriodDays()
            : $this->plan->getServicePeriodDays($date);
    }

    /**
     * It returns the priority of the service based on the next service date and the service period.
     *
     * @return int
     */
    public function getPriority(): int
    {
        if ($this->isHighPriority()) {
            return self::MAX_PRIORITY;
        }

        $serviceTimeWindow = $this->getNextServiceTimeWindow();

        if ($this->getNextServiceDate()->lessThanOrEqualTo($serviceTimeWindow->getStartAt())) {
            return self::MIN_PRIORITY;
        }

        $diffInDays = $serviceTimeWindow->getEndAt()->diffInDays($this->getNextServiceDate());
        $servicePeriodDays = $serviceTimeWindow->getEndAt()->diffInDays($serviceTimeWindow->getStartAt());

        return (int) round(($servicePeriodDays - $diffInDays) / $servicePeriodDays * self::MAX_PRIORITY);
    }

    /**
     * @return Appointment|null
     */
    public function getNextAppointment(): Appointment|null
    {
        return $this->nextAppointment;
    }

    /**
     * @return bool
     */
    public function isRescheduled(): bool
    {
        return $this->nextAppointment !== null;
    }
}
