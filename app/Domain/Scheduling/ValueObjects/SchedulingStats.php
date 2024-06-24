<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\ValueObjects;

readonly class SchedulingStats
{
    public function __construct(
        public int $routesCount,
        public int $appointmentsCount,
        public int $scheduledServicesCount,
        public int $rescheduledServicesCount,
        public int $pendingServicesCount,
        public int $pendingRescheduledServices,
        public int $capacityBeforeScheduling,
        public int $capacityAfterScheduling,
        public int $scheduledHighPriorityServices,
        public int $pendingHighPriorityServices,
    ) {
    }

    /**
     * Returns stats as array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'routes_count' => $this->routesCount,
            'appointments_count' => $this->appointmentsCount,
            'capacity_before_scheduling' => $this->capacityBeforeScheduling,
            'capacity_after_scheduling' => $this->capacityAfterScheduling,
            'scheduled_services_count' => $this->scheduledServicesCount,
            'pending_services_count' => $this->pendingServicesCount,
            'rescheduled_services_count' => $this->rescheduledServicesCount,
            'pending_rescheduled_services' => $this->pendingRescheduledServices,
            'scheduled_high_priority_services' => $this->scheduledHighPriorityServices,
            'pending_high_priority_services' => $this->pendingHighPriorityServices,
        ];
    }
}
