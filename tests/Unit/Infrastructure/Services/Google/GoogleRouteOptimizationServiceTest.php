<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Google;

use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Infrastructure\Services\Google\DataTranslators\DomainToGoogleTranslator;
use App\Infrastructure\Services\Google\DataTranslators\GoogleToDomainTranslator;
use App\Infrastructure\Services\Google\GoogleRouteOptimizationService;
use Google\Cloud\Optimization\V1\Client\FleetRoutingClient;
use Google\Cloud\Optimization\V1\ShipmentModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory as TestOptimizationStateFactory;

class GoogleRouteOptimizationServiceTest extends TestCase
{
    private const PROJECT_ID = 'test_project_id';
    private GoogleRouteOptimizationService $optimizationService;
    private DomainToGoogleTranslator|MockInterface $mockDomainToGoogleTranslator;
    private GoogleToDomainTranslator|MockInterface $mockGoogleToDomainTranslator;
    private FleetRoutingClient|MockInterface $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped('Need to find a way to mock final FleetRoutingClient class');

        $this->mockDomainToGoogleTranslator = Mockery::mock(DomainToGoogleTranslator::class);
        $this->mockGoogleToDomainTranslator = Mockery::mock(GoogleToDomainTranslator::class);
        $this->mockClient = Mockery::mock(FleetRoutingClient::class);

        $this->optimizationService = new GoogleRouteOptimizationService(
            $this->mockDomainToGoogleTranslator,
            $this->mockGoogleToDomainTranslator,
            $this->mockClient,
        );
    }

    /**
     * @test
     *
     * ::optimize
     */
    public function it_optimizes_routes_successfully(): void
    {
        Config::set('googleapis.auth.project_id', self::PROJECT_ID);

        $optimizationState = TestOptimizationStateFactory::make();

        $shipmentModel = new ShipmentModel();
        $this->mockDomainToGoogleTranslator
            ->shouldReceive('translate')
            ->andReturn($shipmentModel);

        $this->mockClient
            ->shouldReceive('optimizeTours')
            ->once();

        $this->mockGoogleToDomainTranslator
            ->shouldReceive('translate')
            ->andReturn($shipmentModel)
            ->andReturn($optimizationState);

        Log::shouldReceive('info')->twice();

        $this->optimizationService->optimize($optimizationState, new Collection());
    }

    /**
     * @test
     */
    public function it_plans_successfully(): void
    {
        $optimizationState = TestOptimizationStateFactory::make();
        $result = $this->optimizationService->plan($optimizationState);

        $this->assertSame($optimizationState, $result);
    }

    /**
     * @test
     */
    public function it_successfully_gets_identifier(): void
    {
        $this->assertSame(OptimizationEngine::GOOGLE, $this->optimizationService->getIdentifier());
    }

    /**
     * @test
     *
     * ::getIdentifier
     */
    public function it_returns_correct_optimization_engine_identifier(): void
    {
        $this->assertSame(OptimizationEngine::GOOGLE, $this->optimizationService->getIdentifier());
    }
}
