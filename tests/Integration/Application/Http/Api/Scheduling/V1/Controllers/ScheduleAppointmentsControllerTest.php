<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\Scheduling\V1\Controllers;

use App\Application\Http\Api\Scheduling\V1\Controllers\ScheduleAppointmentsController;
use App\Application\Managers\ScheduleAppointmentsManager;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\TestValue;

/**
 * @coversDefaultClass ScheduleAppointmentsController
 */
class ScheduleAppointmentsControllerTest extends TestCase
{
    private const ROUTE_NAME = 'scheduling.schedule-appointments-jobs.create';

    private ScheduleAppointmentsManager|MockInterface $scheduleAppointmentsManagerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scheduleAppointmentsManagerMock = Mockery::mock(ScheduleAppointmentsManager::class);
        $this->instance(ScheduleAppointmentsManager::class, $this->scheduleAppointmentsManagerMock);
    }

    /**
     * @test
     */
    public function it_returns_202_when_schedule_appointment_process_started(): void
    {
        $this->scheduleAppointmentsManagerMock
            ->shouldReceive('manage')
            ->once();

        $response = $this->postJson(
            route(self::ROUTE_NAME),
            $this->getValidParameters(),
            $this->getHeaders()
        );

        $response->assertAccepted();
    }

    /**
     * @test
     */
    public function it_returns_400_when_invalid_parameters_passed(): void
    {
        $this->scheduleAppointmentsManagerMock
            ->shouldReceive('manage')
            ->never();

        $response = $response = $this->postJson(
            route(self::ROUTE_NAME),
            [],
            $this->getHeaders()
        );

        $response->assertBadRequest();
    }

    /**
     * @test
     */
    public function it_returns_404_when_office_not_found(): void
    {
        $this->scheduleAppointmentsManagerMock
            ->shouldReceive('manage')
            ->andThrow(OfficeNotFoundException::class);

        $response = $this->postJson(
            route(self::ROUTE_NAME),
            $this->getValidParameters(),
            $this->getHeaders()
        );

        $response->assertNotFound();
    }

    private function getHeaders(): array
    {
        return [];
    }

    private function getValidParameters(): array
    {
        return [
            'office_ids' => [TestValue::OFFICE_ID],
            'start_date' => '2024-01-01',
            'num_days_after_start_date' => 1,
            'num_days_to_schedule' => 5,
        ];
    }
}
