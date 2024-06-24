<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\RouteOptimization\V1\Controllers;

use App\Application\DTO\RouteOptimizationDTO;
use App\Application\Http\Api\RouteOptimization\V1\Controllers\RouteOptimizationController;
use App\Application\Managers\RoutesOptimizationManager;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use Illuminate\Testing\TestResponse;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\TestValue;

/**
 * @coversDefaultClass RouteOptimizationController
 */
class RouteOptimizationControllerTest extends TestCase
{
    private const ROUTE_NAME = 'route-optimization-jobs.create';
    private const OFFICE_IDS = [
        2,
        TestValue::OFFICE_ID,
        120,
    ];
    private const NUMBER_OF_DAYS_AFTER_START_DATE = 1;
    private const NUMBER_OF_DAYS_TO_OPTIMIZE = 2;
    private const DATE = '2023-06-23';

    private MockInterface|RoutesOptimizationManager $mockOptimizationManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupMockOptimizationManager();
    }

    private function setupMockOptimizationManager(): void
    {
        $this->mockOptimizationManager = Mockery::mock(RoutesOptimizationManager::class);
        $this->instance(RoutesOptimizationManager::class, $this->mockOptimizationManager);
    }

    /**
     * @test
     */
    public function it_starts_optimization_process_and_returns_202(): void
    {
        $this->mockOptimizationManager
            ->shouldReceive('manage')
            ->withArgs(function (RouteOptimizationDTO $data) {
                return $data->officeIds === self::OFFICE_IDS
                    && $data->numDaysAfterStartDate === self::NUMBER_OF_DAYS_AFTER_START_DATE
                    && $data->numDaysToOptimize === self::NUMBER_OF_DAYS_TO_OPTIMIZE;
            })
            ->once()
            ->andReturnNull();

        $response = $this->callOptimizeRoute([
            'office_ids' => self::OFFICE_IDS,
            'start_date' => self::DATE,
            'num_days_after_start_date' => self::NUMBER_OF_DAYS_AFTER_START_DATE,
            'num_days_to_optimize' => self::NUMBER_OF_DAYS_TO_OPTIMIZE,
        ]);

        $response->assertAccepted();
        $response->assertJsonPath('_metadata.success', true);
        $response->assertJsonPath('result.message', 'Route optimization initiated successfully.');
    }

    /**
     * @test
     */
    public function it_returns_404_when_office_not_found(): void
    {
        $this->mockOptimizationManager
            ->shouldReceive('manage')
            ->andThrow(new OfficeNotFoundException('Test exception'));

        $response = $this->callOptimizeRoute([
            'office_ids' => self::OFFICE_IDS,
        ]);

        $response->assertNotFound();
        $response->assertJsonPath('_metadata.success', false);
    }

    /**
     * @test
     */
    public function it_returns_400_when_invalid_parameters_passed(): void
    {
        $this->mockOptimizationManager
            ->shouldReceive('manage')
            ->never();

        $response = $this->callOptimizeRoute([]);

        $response->assertBadRequest();
    }

    private function callOptimizeRoute(array $requestData): TestResponse
    {
        return $this->postJson(
            route(self::ROUTE_NAME),
            $requestData
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->mockOptimizationManager);
    }
}
