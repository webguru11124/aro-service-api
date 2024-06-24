<?php

declare(strict_types=1);

namespace App\Infrastructure\Formatters;

use App\Domain\Scheduling\Entities\Appointment;
use App\Domain\Scheduling\Entities\PendingService;
use App\Domain\Scheduling\Entities\ScheduledRoute;
use App\Domain\SharedKernel\ValueObjects\Coordinate;

class ScheduledRouteArrayFormatter
{
    public function __construct(
        private PendingServiceArrayFormatter $pendingServiceFormatter,
    ) {
    }

    /**
     * Formats the Route as an array
     *
     * @param ScheduledRoute $scheduledRoute
     *
     * @return mixed[]
     */
    public function format(ScheduledRoute $scheduledRoute): array
    {
        return [
            'details' => $this->formatDetails($scheduledRoute),
            'service_pro' => $this->formatServicePro($scheduledRoute),
            'appointments' => $this->formatAppointments($scheduledRoute),
            'pending_services' => $this->formatPendingServices($scheduledRoute),
        ];
    }

    /**
     * @return mixed[]
     */
    private function formatDetails(ScheduledRoute $scheduledRoute): array
    {
        return [
            'capacity' => $scheduledRoute->getCapacity(),
            'actual_capacity' => $scheduledRoute->getActualCapacityCount(),
            'route_type' => $scheduledRoute->getRouteType()->value,
            'date' => $scheduledRoute->getDate()->toDateString(),
        ];
    }

    /**
     * @return mixed[]
     */
    private function formatServicePro(ScheduledRoute $scheduledRoute): array
    {
        $servicePro = $scheduledRoute->getServicePro();

        return [
            'id' => $servicePro->getId(),
            'name' => $servicePro->getName(),
            'location' => $this->formatLocation($servicePro->getStartLocation()),
        ];
    }

    /**
     * @return mixed[]
     */
    private function formatAppointments(ScheduledRoute $scheduledRoute): array
    {
        $appointments = $scheduledRoute->getAppointments();

        return $appointments->map(function (Appointment $appointment) {
            return [
                'id' => $appointment->getId(),
                'initial' => $appointment->isInitial(),
                'date' => $appointment->getDate()->toDateString(),
                'date_completed' => $appointment->getDateCompleted()?->toDateString(),
                'location' => $this->formatLocation($appointment->getLocation()),
                'customer' => [
                    'id' => $appointment->getCustomer()->getId(),
                    'name' => $appointment->getCustomer()->getName(),
                ],
                'duration' => $appointment->getDuration()->getTotalMinutes(),
            ];
        })->toArray();
    }

    /**
     * @return mixed[]
     */
    private function formatPendingServices(ScheduledRoute $scheduledRoute): array
    {
        return $scheduledRoute->getPendingServices()->map(
            fn (PendingService $pendingService) => $this->pendingServiceFormatter->format($pendingService)
        )->values()->toArray();
    }

    /**
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
