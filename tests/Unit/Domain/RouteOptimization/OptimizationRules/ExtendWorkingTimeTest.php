<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\OptimizationRules;

use App\Domain\Contracts\FeatureFlagService;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\OptimizationRules\ExtendWorkingTime;
use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Domain\RouteOptimization\ValueObjects\RouteType;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Traits\AssertRuleExecutionResultsTrait;
use Tests\Traits\RuleGetNameAndDescription;

class ExtendWorkingTimeTest extends TestCase
{
    use AssertRuleExecutionResultsTrait;
    use RuleGetNameAndDescription;

    private const WORKING_DAY_START = '08:00:00';
    private const WORKING_DAY_HOURS = 8;
    private const EXTEND_MINUTES = 30;
    private const MAX_WORKING_TIME = 600;

    private MockInterface|FeatureFlagService $mockFeatureFlagService;

    private ExtendWorkingTime $rule;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('aptive.max_working_time.summer.default', self::MAX_WORKING_TIME);
        Config::set('aptive.max_working_time.winter.default', self::MAX_WORKING_TIME);

        $this->mockFeatureFlagService = Mockery::mock(FeatureFlagService::class);
        $this->rule = new ExtendWorkingTime($this->mockFeatureFlagService);
    }

    /**
     * @test
     */
    public function it_applies_rule_correctly(): void
    {
        $mockRoute = Mockery::mock(Route::class);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$mockRoute],
        ]);

        $workingDayTimeWindow = new TimeWindow(
            Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START),
            Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START)->addHours(self::WORKING_DAY_HOURS),
        );
        $extendedTimeWindow = new TimeWindow(
            $workingDayTimeWindow->getStartAt()->clone(),
            $workingDayTimeWindow->getEndAt()->clone()->addMinutes(self::EXTEND_MINUTES),
        );

        $mockRoute->shouldReceive('getTimeWindow')
            ->once()
            ->andReturn($workingDayTimeWindow);
        $mockRoute->shouldReceive('getRouteType')
            ->once()
            ->andReturn(RouteType::REGULAR_ROUTE);
        $mockRoute->shouldReceive('setTimeWindow')
            ->once()
            ->withArgs(function (TimeWindow $timeWindow) use ($extendedTimeWindow) {
                return $timeWindow->getStartAt() == $extendedTimeWindow->getStartAt()
                    && $timeWindow->getEndAt() == $extendedTimeWindow->getEndAt();
            });

        $this->mockFeatureFlagService->shouldReceive('isFeatureEnabledForOffice')
            ->andReturnFalse();

        $this->rule->process($optimizationState, OptimizationStateFactory::make());

        $this->assertSuccessRuleResult($optimizationState->getRuleExecutionResults()->first());
    }

    /**
     * @test
     */
    public function it_does_not_extend_time_window_more_than_max_allowed_working_time(): void
    {
        $mockRoute = Mockery::mock(Route::class);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$mockRoute],
        ]);

        $workingDayTimeWindow = new TimeWindow(
            Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START),
            Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START)->addMinutes(self::MAX_WORKING_TIME),
        );
        $extendedTimeWindow = new TimeWindow(
            $workingDayTimeWindow->getStartAt()->clone(),
            $workingDayTimeWindow->getEndAt()->clone(),
        );

        $mockRoute->shouldReceive('getRouteType')
            ->once()
            ->andReturn(RouteType::REGULAR_ROUTE);
        $mockRoute->shouldReceive('getTimeWindow')
            ->once()
            ->andReturn($workingDayTimeWindow);
        $mockRoute->shouldReceive('setTimeWindow')
            ->once()
            ->withArgs(function (TimeWindow $timeWindow) use ($extendedTimeWindow) {
                return $timeWindow->getStartAt() == $extendedTimeWindow->getStartAt()
                    && $timeWindow->getEndAt() == $extendedTimeWindow->getEndAt();
            });

        $this->mockFeatureFlagService->shouldReceive('isFeatureEnabledForOffice')
            ->andReturnFalse();

        $this->rule->process($optimizationState, OptimizationStateFactory::make());

        $this->assertSuccessRuleResult($optimizationState->getRuleExecutionResults()->first());
    }

    /**
     * @test
     */
    public function it_does_not_apply_rule_when_restrict_time_window_is_enabled(): void
    {
        $mockRoute = Mockery::mock(Route::class);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$mockRoute],
        ]);

        $this->mockFeatureFlagService->shouldReceive('isFeatureEnabledForOffice')
            ->andReturnTrue();

        $mockRoute->shouldReceive('setTimeWindow')
            ->never();

        $this->rule->process($optimizationState, OptimizationStateFactory::make());

        $this->assertTriggeredRuleResult($optimizationState->getRuleExecutionResults()->first());
    }

    /**
     * @test
     */
    public function it_does_not_apply_rule_when_it_is_disabled(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'optimizationParams' => new OptimizationParams(disabledRules: ['ExtendWorkingTime']),
        ]);

        $this->rule->process($optimizationState, OptimizationStateFactory::make());

        $this->assertSkippedRuleResult($optimizationState->getRuleExecutionResults()->first());
    }

    protected function getClassRuleName(): string
    {
        return ExtendWorkingTime::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->rule, $this->mockFeatureFlagService);
    }
}
