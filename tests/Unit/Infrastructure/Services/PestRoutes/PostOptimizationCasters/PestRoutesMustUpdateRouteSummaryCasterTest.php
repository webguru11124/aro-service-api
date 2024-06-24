<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\PestRoutes\PostOptimizationCasters;

use App\Domain\Contracts\FeatureFlagService;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\PostOptimizationRules\MustUpdateRouteSummary;
use App\Domain\RouteOptimization\Services\RouteStatisticsService;
use App\Domain\RouteOptimization\ValueObjects\RouteConfig;
use App\Domain\RouteOptimization\ValueObjects\RouteSummary;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\SpotsDataProcessor;
use App\Infrastructure\Services\ConfigCat\ConfigCatService;
use App\Infrastructure\Services\PestRoutes\PostOptimizationCasters\PestRoutesMustUpdateRouteSummaryCaster;
use Aptive\PestRoutesSDK\Resources\Spots\Params\SearchSpotsParams;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\TotalWeightedServiceMetricFactory;
use Tests\Tools\PestRoutesData\SpotData;
use Tests\Traits\AssertRuleExecutionResultsTrait;

class PestRoutesMustUpdateRouteSummaryCasterTest extends TestCase
{
    use AssertRuleExecutionResultsTrait;

    private MockInterface|SpotsDataProcessor $spotsDataProcessorMock;
    private MockInterface|RouteStatisticsService $routeStatisticsServiceMock;
    private MockInterface|FeatureFlagService $mockFeatureFlagService;
    private PestRoutesMustUpdateRouteSummaryCaster $caster;
    private MustUpdateRouteSummary $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->spotsDataProcessorMock = Mockery::mock(SpotsDataProcessor::class);
        $this->routeStatisticsServiceMock = Mockery::mock(RouteStatisticsService::class);
        $this->mockFeatureFlagService = Mockery::mock(ConfigCatService::class);

        $this->caster = new PestRoutesMustUpdateRouteSummaryCaster(
            $this->spotsDataProcessorMock,
            $this->routeStatisticsServiceMock,
            $this->mockFeatureFlagService,
        );

        $this->rule = new MustUpdateRouteSummary();
    }

    /**
     * @test
     */
    public function it_adds_route_summary_with_stats(): void
    {
        /** @var Route $route */
        $route = RouteFactory::make([
            'config' => new RouteConfig(2, 1),
        ]);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make(['routes' => [$route]]);

        $nowDate = Carbon::now($optimizationState->getOffice()->getTimeZone());
        Carbon::setTestNow($nowDate);

        $routeSummary = new RouteSummary(
            drivingTime: Duration::fromMinutes($this->faker->randomNumber(2)),
            servicingTime: Duration::fromMinutes($this->faker->randomNumber(2)),
            totalWorkingTime: Duration::fromMinutes($this->faker->randomNumber(2)),
            asOf: $nowDate,
            excludeFirstAppointment: true
        );

        $this->routeStatisticsServiceMock
            ->shouldReceive('getRouteSummary')
            ->withArgs(function (Route $routeArg, CarbonInterface $dateArg) use ($route, $nowDate) {
                return $routeArg === $route
                    && $dateArg->timestamp === $nowDate->timestamp
                    && $dateArg->timezone->getName() === $nowDate->timezone->getName();
            })
            ->once()
            ->andReturn($routeSummary);

        $spots = SpotData::getTestData(
            3,
            ['start' => '10:00:00', 'end' => '10:30:00', 'routeID' => $route->getId()],
            ['start' => '11:00:00', 'end' => '11:30:00', 'routeID' => $route->getId()],
            ['start' => '08:00:00', 'end' => '08:30:00', 'routeID' => $route->getId()],
        );

        $this->spotsDataProcessorMock
            ->shouldReceive('extract')
            ->withArgs(function (int $officeId, SearchSpotsParams $params) use ($route, $optimizationState) {
                $array = $params->toArray();

                return $officeId === $optimizationState->getOffice()->getId()
                    && $array['officeIDs'] === [$optimizationState->getOffice()->getId()]
                    && $array['routeIDs'] === [$route->getId()];
            })
            ->once()
            ->andReturn($spots);

        $blockReason = $this->rule->formatRouteSummary($routeSummary);

        $this->spotsDataProcessorMock
            ->shouldReceive('block')
            ->withArgs([$route->getOfficeId(), $spots->get(1)->id, $blockReason])
            ->once()
            ->andReturnTrue();

        $this->mockFeatureFlagService->shouldReceive('isFeatureEnabledForOffice')
            ->once()
            ->andReturn(false);

        $result = $this->caster->process(Carbon::tomorrow(), $optimizationState, $this->rule);

        $this->assertSuccessRuleResult($result);
    }

    /**
     * @test
     */
    public function it_adds_route_summary_with_scores(): void
    {
        /** @var Route $route1 */
        $route1 = RouteFactory::make([
            'metrics' => [TotalWeightedServiceMetricFactory::make(['value' => 10])],
            'config' => new RouteConfig(2, 1),
        ]);
        /** @var Route $route2 */
        $route2 = RouteFactory::make([
            'metrics' => [TotalWeightedServiceMetricFactory::make(['value' => 12])],
            'config' => new RouteConfig(2, 1),
        ]);
        /** @var Route $route3 */
        $route3 = RouteFactory::make([
            'metrics' => [],
            'config' => new RouteConfig(2, 1),
        ]);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make(['routes' => [$route1, $route2, $route3]]);

        $nowDate = Carbon::now($optimizationState->getOffice()->getTimeZone());
        Carbon::setTestNow($nowDate);

        $this->mockFeatureFlagService->shouldReceive('isFeatureEnabledForOffice')
            ->once()
            ->andReturn(true);

        $this->routeStatisticsServiceMock
            ->shouldReceive('getRouteSummary')
            ->never();

        $spots = SpotData::getTestData(
            9,
            ['start' => '08:00:00', 'end' => '08:30:00', 'routeID' => $route1->getId()],
            ['start' => '10:00:00', 'end' => '10:30:00', 'routeID' => $route1->getId()],
            ['start' => '11:00:00', 'end' => '11:30:00', 'routeID' => $route1->getId()],
            ['start' => '08:00:00', 'end' => '08:30:00', 'routeID' => $route2->getId()],
            ['start' => '10:00:00', 'end' => '10:30:00', 'routeID' => $route2->getId()],
            ['start' => '11:00:00', 'end' => '11:30:00', 'routeID' => $route2->getId()],
            ['start' => '08:00:00', 'end' => '08:30:00', 'routeID' => $route3->getId()],
            ['start' => '10:00:00', 'end' => '10:30:00', 'routeID' => $route3->getId()],
            ['start' => '11:00:00', 'end' => '11:30:00', 'routeID' => $route3->getId()],
        );

        $this->spotsDataProcessorMock
            ->shouldReceive('extract')
            ->withArgs(function (int $officeId, SearchSpotsParams $params) use ($route1, $route2, $route3, $optimizationState) {
                $array = $params->toArray();

                return $officeId === $optimizationState->getOffice()->getId()
                    && $array['officeIDs'] === [$optimizationState->getOffice()->getId()]
                    && $array['routeIDs'] === [$route1->getId(), $route2->getId(), $route3->getId()];
            })
            ->once()
            ->andReturn($spots);

        $blockReason1 = 'ARO Summary: Route Optimization Score: 71%, Office Optimization Score: 52%, As of: '
            . $nowDate->format('M d, h:iA T')
            . '.';
        $this->spotsDataProcessorMock
            ->shouldReceive('block')
            ->withArgs([$route1->getOfficeId(), $spots->get(2)->id, $blockReason1])
            ->once()
            ->andReturnTrue();

        $blockReason2 = 'ARO Summary: Route Optimization Score: 86%, Office Optimization Score: 52%, As of: '
            . $nowDate->format('M d, h:iA T')
            . '.';
        $this->spotsDataProcessorMock
            ->shouldReceive('block')
            ->withArgs([$route2->getOfficeId(), $spots->get(5)->id, $blockReason2])
            ->once()
            ->andReturnTrue();

        $blockReason3 = 'ARO Summary: Route Optimization Score: 0%, Office Optimization Score: 52%, As of: '
            . $nowDate->format('M d, h:iA T')
            . '.';
        $this->spotsDataProcessorMock
            ->shouldReceive('block')
            ->withArgs([$route3->getOfficeId(), $spots->get(8)->id, $blockReason3])
            ->once()
            ->andReturnTrue();

        $this->caster->process(Carbon::tomorrow(), $optimizationState, $this->rule);
    }

    /**
     * @test
     */
    public function it_does_not_add_summary_when_there_are_no_routes(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make(['routes' => []]);

        $this->routeStatisticsServiceMock
            ->shouldReceive('getRouteSummary')
            ->never();

        $this->spotsDataProcessorMock
            ->shouldReceive('extract')
            ->never();

        $this->mockFeatureFlagService->shouldReceive('isFeatureEnabledForOffice')
            ->once()
            ->andReturn(false);

        $result = $this->caster->process(Carbon::tomorrow(), $optimizationState, $this->rule);

        $this->assertSuccessRuleResult($result);
    }

    /**
     * @test
     */
    public function it_does_not_add_summary_when_there_are_no_spots(): void
    {
        /** @var Route $route */
        $route = RouteFactory::make([
            'config' => new RouteConfig(2, 1),
        ]);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make(['routes' => [$route]]);

        $routeSummary = new RouteSummary(
            drivingTime: Duration::fromMinutes($this->faker->randomNumber(2)),
            servicingTime: Duration::fromMinutes($this->faker->randomNumber(2)),
            totalWorkingTime: Duration::fromMinutes($this->faker->randomNumber(2)),
            asOf: Carbon::now($optimizationState->getOffice()->getTimeZone()),
            excludeFirstAppointment: true
        );

        $this->routeStatisticsServiceMock
            ->shouldReceive('getRouteSummary')
            ->once()
            ->andReturn($routeSummary);

        $this->spotsDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->andReturn(new Collection());

        $this->spotsDataProcessorMock
            ->shouldReceive('block')
            ->never();

        $this->mockFeatureFlagService->shouldReceive('isFeatureEnabledForOffice')
            ->once()
            ->andReturn(false);

        $this->caster->process(Carbon::tomorrow(), $optimizationState, $this->rule);
    }

    protected function getClassRuleName(): string
    {
        return get_class($this->rule);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->caster);
        unset($this->rule);
        unset($this->spotsDataProcessorMock);
        unset($this->routeStatisticsServiceMock);
        unset($this->mockFeatureFlagService);
    }
}
