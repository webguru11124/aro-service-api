<?php

declare(strict_types=1);

namespace App\Infrastructure\Formatters;

use App\Domain\Scheduling\Entities\PendingService;
use App\Domain\SharedKernel\ValueObjects\Coordinate;

class PendingServiceArrayFormatter
{
    /**
     * @param PendingService $pendingService
     *
     * @return mixed[]
     */
    public function format(PendingService $pendingService): array
    {
        return [
            'customer' => [
                'id' => $pendingService->getCustomer()->getId(),
                'name' => $pendingService->getCustomer()->getName(),
            ],
            'subscription' => [
                'id' => $pendingService->getSubscriptionId(),
                'service_type_id' => $pendingService->getPlan()->getServiceTypeId(),
                'plan_name' => $pendingService->getPlan()->getName(),
            ],
            'location' => $this->formatLocation($pendingService->getLocation()),
            'is_high_priority' => (int) $pendingService->isHighPriority(),
            'priority' => $pendingService->getPriority(),
            'next_service_date' => $pendingService->getNextServiceDate()->toDateString(),
            'next_service_window' => [
                'start' => $pendingService->getNextServiceTimeWindow()->getStartAt()->toDateString(),
                'end' => $pendingService->getNextServiceTimeWindow()->getEndAt()->toDateString(),
            ],
            'previous_appointment' => [
                'id' => $pendingService->getPreviousAppointment()->getId(),
                'date' => $pendingService->getPreviousAppointment()->getDate()->toDateString(),
                'is_initial' => $pendingService->getPreviousAppointment()->isInitial(),
            ],
            'customer_preferences' => [
                'start' => $pendingService->getPreferredStart(),
                'end' => $pendingService->getPreferredEnd(),
                'employee_id' => $pendingService->getPreferredEmployeeId(),
                'day' => $pendingService->getPreferredDay(),
            ],
            'is_rescheduled' => $pendingService->isRescheduled(),
            'next_appointment' => $pendingService->isRescheduled() ? [
                'id' => $pendingService->getNextAppointment()->getId(),
                'date' => $pendingService->getNextAppointment()->getDate()->toDateString(),
                'is_initial' => $pendingService->getNextAppointment()->isInitial(),
            ] : null,
        ];
    }

    /**
     * @param Coordinate $coordinate
     *
     * @return array<string, float>
     */
    private function formatLocation(Coordinate $coordinate): array
    {
        return [
            'lat' => $coordinate->getLatitude(),
            'lng' => $coordinate->getLongitude(),
        ];
    }
}
