<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Factories;

use App\Domain\Contracts\Queries\Office\OfficeQuery;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use App\Domain\RouteOptimization\Factories\OptimizationStateArrayFactory;
use App\Domain\RouteOptimization\Factories\RouteFactory;
use App\Domain\RouteOptimization\Factories\ServiceProFactory;
use App\Domain\RouteOptimization\Factories\WorkEventFactory;
use App\Domain\SharedKernel\Entities\Office;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\TestValue;

class OptimizationStateArrayFactoryTest extends TestCase
{
    private OptimizationStateArrayFactory $optimizationStateFactory;

    private MockInterface|OfficeQuery $mockOfficeQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockOfficeQuery = Mockery::mock(OfficeQuery::class);

        $this->optimizationStateFactory = new OptimizationStateArrayFactory(
            $this->mockOfficeQuery,
            app(RouteFactory::class),
            app(ServiceProFactory::class),
            app(WorkEventFactory::class)
        );
    }

    /**
     * @test
     */
    public function it_creates_optimization_state_from_provided_data(): void
    {
        /** @var Office $office */
        $office = OfficeFactory::make();

        $routeData = [
            'id' => TestValue::ROUTE_ID,
            'details' => [
                'start_at' => '2024-04-01 08:00:00',
                'route_type' => 'Regular Route',
                'actual_capacity' => 10,
            ],
            'service_pro' => [
                'id' => TestValue::EMPLOYEE_ID,
                'name' => $this->faker->name(),
                'workday_id' => TestValue::WORKDAY_ID,
                'start_location' => ['lat' => 1.0, 'lon' => 1.0],
                'end_location' => ['lat' => 2.0, 'lon' => 2.0],
                'working_hours' => [
                    'start_at' => '08:00:00',
                    'end_at' => '16:00:00',
                ],
                'capacity' => 10,
                'skills' => ['TX'],
            ],
            'schedule' => [
                [
                    'work_event_type' => 'Start Location',
                    'description' => 'Start',
                    'location' => ['lat' => TestValue::MAX_LATITUDE, 'lon' => TestValue::MAX_LONGITUDE],
                    'scheduled_time_window' => ['end' => '2024-04-01 08:00:00', 'start' => '2024-04-01 08:00:00'],
                ],
            ],
        ];

        $this->mockOfficeQuery
            ->shouldReceive('get')
            ->with(TestValue::OFFICE_ID)
            ->andReturn($office);

        $data = [
            'id' => TestValue::ROUTE_ID,
            'office' => [
                'office_id' => TestValue::OFFICE_ID,
            ],
            'state' => [
                'engine' => OptimizationEngine::VROOM->value,
                'created_at' => '2024-04-01 08:00:00',
                'optimization_window_start' => '2024-04-01 08:00:00',
                'optimization_window_end' => '2024-04-01 16:00:00',
            ],
            'status' => OptimizationStatus::PRE,
            'routes' => collect([$routeData]),
        ];

        $state = $this->optimizationStateFactory->make($data);

        $this->assertInstanceOf(OptimizationState::class, $state);
        $this->assertEquals($data['id'], $state->getId());
        $this->assertEquals(OptimizationEngine::VROOM, $state->getEngine());
        $this->assertEquals(OptimizationStatus::PRE, $state->getStatus());
        $this->assertEquals('2024-04-01 08:00:00', $state->getCreatedAt()->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-04-01 08:00:00', $state->getOptimizationTimeFrame()->getStartAt()->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-04-01 16:00:00', $state->getOptimizationTimeFrame()->getEndAt()->format('Y-m-d H:i:s'));
        $this->assertEquals($office, $state->getOffice());
    }
}
