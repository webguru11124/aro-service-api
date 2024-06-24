<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\PestRoutes;

use App\Domain\RouteOptimization\PostOptimizationRules\MustHaveBlockedInsideSales;
use App\Infrastructure\Services\PestRoutes\PestRoutesOptimizationPostProcessService;
use App\Infrastructure\Services\PestRoutes\PostOptimizationCasters\PestRoutesBlockedInsideSalesCaster;
use Illuminate\Support\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory;

class PestRoutesOptimizationPostProcessServiceTest extends TestCase
{
    private PestRoutesOptimizationPostProcessService $service;
    private MockInterface|PestRoutesBlockedInsideSalesCaster $mockCaster;

    protected function setUp(): void
    {
        parent::setUp();

        app()->bind(PestRoutesOptimizationPostProcessService::class, function () {
            return new PestRoutesOptimizationPostProcessService([
                new MustHaveBlockedInsideSales(),
            ]);
        });

        $this->mockCaster = Mockery::mock(PestRoutesBlockedInsideSalesCaster::class);
        app()->bind(
            PestRoutesBlockedInsideSalesCaster::class,
            fn () => $this->mockCaster
        );

        $this->service = app(PestRoutesOptimizationPostProcessService::class);
    }

    /**
     * @test
     */
    public function it_runs_post_optimization_registered_actions(): void
    {
        $date = Carbon::parse('2023-08-08');
        $optimizationState = OptimizationStateFactory::make();

        $this->mockCaster
            ->shouldReceive('process')
            ->once();

        $this->service->execute($date, $optimizationState);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->mockCaster);
        unset($this->service);
    }
}
