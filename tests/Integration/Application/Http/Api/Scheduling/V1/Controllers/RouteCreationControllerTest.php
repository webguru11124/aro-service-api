<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\Scheduling\V1\Controllers;

use App\Application\Managers\RoutesCreationManager;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\TestValue;

class RouteCreationControllerTest extends TestCase
{
    private const ROUTE_NAME = 'scheduling.route-creation-jobs.create';

    private RoutesCreationManager|MockInterface $routesCreationManagerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->routesCreationManagerMock = Mockery::mock(RoutesCreationManager::class);
        $this->instance(RoutesCreationManager::class, $this->routesCreationManagerMock);
    }

    /**
     * @test
     */
    public function it_returns_202_when_schedule_appointment_process_started(): void
    {
        $this->routesCreationManagerMock
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
        $this->routesCreationManagerMock
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
        $this->routesCreationManagerMock
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
            'num_days_to_create_routes' => 2,
        ];
    }
}
