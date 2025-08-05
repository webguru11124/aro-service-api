<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Formatters;

use App\Domain\Scheduling\Entities\PendingService;
use App\Infrastructure\Formatters\PendingServiceArrayFormatter;
use Tests\TestCase;
use Tests\Tools\Factories\Scheduling\AppointmentFactory;
use Tests\Tools\Factories\Scheduling\PendingServiceFactory;

class PendingServiceArrayFormatterTest extends TestCase
{
    private PendingServiceArrayFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter = new PendingServiceArrayFormatter();
    }

    /**
     * @test
     */
    public function it_can_format_pending_service_as_an_array(): void
    {
        /** @var PendingService $pendingService */
        $pendingService = PendingServiceFactory::make([
            'previousAppointment' => AppointmentFactory::make(),
        ]);

        $expectedFormat = [
            'customer' => [
                'id' => $pendingService->getCustomer()->getId(),
                'name' => $pendingService->getCustomer()->getName(),
            ],
            'subscription' => [
                'id' => $pendingService->getSubscriptionId(),
                'service_type_id' => $pendingService->getPlan()->getServiceTypeId(),
                'plan_name' => $pendingService->getPlan()->getName(),
            ],
            'location' => [
                'lat' => $pendingService->getLocation()->getLatitude(),
                'lng' => $pendingService->getLocation()->getLongitude(),
            ],
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

        $formattedState = $this->formatter->format($pendingService);

        $this->assertIsArray($formattedState);
        $this->assertEquals($expectedFormat, $formattedState);
    }
}
