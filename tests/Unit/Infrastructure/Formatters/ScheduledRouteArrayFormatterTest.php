<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Formatters;

use App\Domain\Scheduling\Entities\Appointment;
use App\Domain\Scheduling\Entities\PendingService;
use App\Domain\Scheduling\Entities\ScheduledRoute;
use App\Infrastructure\Formatters\PendingServiceArrayFormatter;
use App\Infrastructure\Formatters\ScheduledRouteArrayFormatter;
use Tests\TestCase;
use Tests\Tools\Factories\Scheduling\AppointmentFactory;
use Tests\Tools\Factories\Scheduling\PendingServiceFactory;
use Tests\Tools\Factories\Scheduling\ScheduledRouteFactory;

class ScheduledRouteArrayFormatterTest extends TestCase
{
    private ScheduledRouteArrayFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter = new ScheduledRouteArrayFormatter(
            app(PendingServiceArrayFormatter::class)
        );
    }

    /**
     * @test
     */
    public function it_can_format_scheduled_route_as_an_array(): void
    {
        /** @var Appointment $appointment */
        $appointment = AppointmentFactory::make();
        /** @var PendingService $scheduledPendingService */
        $scheduledPendingService = PendingServiceFactory::make();
        /** @var ScheduledRoute $scheduledRoute */
        $scheduledRoute = ScheduledRouteFactory::make([
            'pendingServices' => [$scheduledPendingService],
            'appointments' => [$appointment],
        ]);

        $expectedFormat = [
            'service_pro' => [
                'id' => $scheduledRoute->getServicePro()->getId(),
                'name' => $scheduledRoute->getServicePro()->getName(),
                'location' => [
                    'lat' => $scheduledRoute->getServicePro()->getStartLocation()->getLatitude(),
                    'lng' => $scheduledRoute->getServicePro()->getStartLocation()->getLongitude(),
                ],
            ],
            'appointments' => [
                [
                    'id' => $appointment->getId(),
                    'initial' => $appointment->isInitial(),
                    'date' => $appointment->getDate()->toDateString(),
                    'date_completed' => $appointment->getDateCompleted()?->toDateString(),
                    'location' => [
                        'lat' => $appointment->getLocation()->getLatitude(),
                        'lng' => $appointment->getLocation()->getLongitude(),
                    ],
                    'customer' => [
                        'id' => $appointment->getCustomer()->getId(),
                        'name' => $appointment->getCustomer()->getName(),
                    ],
                    'duration' => $appointment->getDuration()->getTotalMinutes(),
                ],
            ],
            'pending_services' => [
                [
                    'customer' => [
                        'id' => $scheduledPendingService->getCustomer()->getId(),
                        'name' => $scheduledPendingService->getCustomer()->getName(),
                    ],
                    'subscription' => [
                        'id' => $scheduledPendingService->getSubscriptionId(),
                        'service_type_id' => $scheduledPendingService->getPlan()->getServiceTypeId(),
                        'plan_name' => $scheduledPendingService->getPlan()->getName(),
                    ],
                    'location' => [
                        'lat' => $scheduledPendingService->getLocation()->getLatitude(),
                        'lng' => $scheduledPendingService->getLocation()->getLongitude(),
                    ],
                    'is_high_priority' => (int) $scheduledPendingService->isHighPriority(),
                    'priority' => $scheduledPendingService->getPriority(),
                    'next_service_date' => $scheduledPendingService->getNextServiceDate()->toDateString(),
                    'next_service_window' => [
                        'start' => $scheduledPendingService->getNextServiceTimeWindow()->getStartAt()->toDateString(),
                        'end' => $scheduledPendingService->getNextServiceTimeWindow()->getEndAt()->toDateString(),
                    ],
                    'previous_appointment' => [
                        'id' => $scheduledPendingService->getPreviousAppointment()->getId(),
                        'date' => $scheduledPendingService->getPreviousAppointment()->getDate()->toDateString(),
                        'is_initial' => $scheduledPendingService->getPreviousAppointment()->isInitial(),
                    ],
                    'customer_preferences' => [
                        'start' => $scheduledPendingService->getPreferredStart(),
                        'end' => $scheduledPendingService->getPreferredEnd(),
                        'employee_id' => $scheduledPendingService->getPreferredEmployeeId(),
                        'day' => $scheduledPendingService->getPreferredDay(),
                    ],
                    'is_rescheduled' => $scheduledPendingService->isRescheduled(),
                    'next_appointment' => null,
                ],
            ],
            'details' => [
                'capacity' => $scheduledRoute->getCapacity(),
                'actual_capacity' => $scheduledRoute->getActualCapacityCount(),
                'route_type' => $scheduledRoute->getRouteType()->value,
                'date' => $scheduledRoute->getDate()->toDateString(),
            ],
        ];

        $formattedState = $this->formatter->format($scheduledRoute);

        $this->assertIsArray($formattedState);
        $this->assertEquals($expectedFormat, $formattedState);
    }
}
