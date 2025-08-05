<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services;

use App\Domain\Contracts\Queries\Office\GetAllOfficesQuery;
use App\Domain\Contracts\Queries\Office\GetOfficeQuery;
use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Infrastructure\Services\OptimizationSandboxDataService;
use App\Infrastructure\Services\PestRoutes\PestRoutesOptimizationStateCached;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\Factories\OptimizationStateFactory;

class OptimizationSandboxDataServiceTest extends TestCase
{
    private GetAllOfficesQuery|MockInterface $officesQueryMock;
    private PestRoutesOptimizationStateCached|MockInterface $optimizationStateResolverMock;
    private GetOfficeQuery|MockInterface $officeQueryMock;

    private OptimizationSandboxDataService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMocks();

        $this->service = new OptimizationSandboxDataService(
            $this->officesQueryMock,
            $this->optimizationStateResolverMock,
            $this->officeQueryMock,
        );
    }

    protected function setUpMocks(): void
    {
        $this->officesQueryMock = Mockery::mock(GetAllOfficesQuery::class);
        $this->optimizationStateResolverMock = Mockery::mock(PestRoutesOptimizationStateCached::class);
        $this->officeQueryMock = Mockery::mock(GetOfficeQuery::class);
    }

    /**
     * @test
     */
    public function it_returns_list_of_offices(): void
    {
        $this->officesQueryMock
            ->shouldReceive('get')
            ->andReturn(collect(OfficeFactory::many(2)));

        $result = $this->service->getOffices();

        $this->assertCount(2, $result);

        foreach ($result as $id => $name) {
            $this->assertIsInt($id);
            $this->assertIsString($name);
        }
    }

    /**
     * @test
     */
    public function it_returns_state_for_overview(): void
    {
        $officeId = 1;
        $optimizationDate = Carbon::now();

        $office = OfficeFactory::make(['id' => $officeId]);

        $preOptimizationState = OptimizationStateFactory::make();

        $this->officeQueryMock
            ->shouldReceive('get')
            ->once()
            ->with($officeId)
            ->andReturn($office);

        $this->optimizationStateResolverMock
            ->shouldReceive('resolve')
            ->once()
            ->with($optimizationDate, $office, Mockery::type(OptimizationParams::class))
            ->andReturn($preOptimizationState);

        $result = $this->service->getStateForOverview($officeId, $optimizationDate);

        $this->assertEquals($preOptimizationState, $result);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->officesQueryMock);
        unset($this->optimizationStateResolverMock);
        unset($this->officeQueryMock);
        unset($this->service);
    }
}
