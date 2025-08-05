<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\OptimizationRules;

use App\Domain\Contracts\FeatureFlagService;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\OptimizationRules\RestrictTimeWindow;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\TestValue;
use Tests\Traits\AssertRuleExecutionResultsTrait;
use Tests\Traits\RuleGetNameAndDescription;

class RestrictTimeWindowTest extends TestCase
{
    use AssertRuleExecutionResultsTrait;
    use RuleGetNameAndDescription;

    private const START_HOUR = 8;
    private const END_HOUR = 18;
    private const FEATURE_FLAG = 'isRestrictServiceProTimeAvailabilityEnabled';

    private RestrictTimeWindow $rule;
    private MockInterface|FeatureFlagService $mockFeatureFlagService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockFeatureFlagService = Mockery::mock(FeatureFlagService::class);
        $this->rule = new RestrictTimeWindow($this->mockFeatureFlagService);
    }

    /**
     * @test
     */
    public function it_applies_rule_correctly_when_feature_flag_is_enabled(): void
    {
        $mockRoute = Mockery::mock(Route::class);
        $officeId = TestValue::OFFICE_ID;

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'officeId' => $officeId,
            'routes' => [$mockRoute],
        ]);

        $this->mockFeatureFlagService->shouldReceive('isFeatureEnabledForOffice')
            ->once()
            ->withSomeOfArgs(self::FEATURE_FLAG)
            ->andReturnTrue();

        $timeWindow = new TimeWindow(
            Carbon::createFromTime(6, 0),
            Carbon::createFromTime(20, 0),
        );
        $expectedTimeWindow = new TimeWindow(
            Carbon::createFromTime(self::START_HOUR, 0),
            Carbon::createFromTime(self::END_HOUR, 0),
        );

        $mockRoute->shouldReceive('getTimeWindow')
            ->once()
            ->andReturn($timeWindow);
        $mockRoute->shouldReceive('setTimeWindow')
            ->once()
            ->withArgs(function (TimeWindow $timeWindow) use ($expectedTimeWindow) {
                return $timeWindow->getStartAt() == $expectedTimeWindow->getStartAt()
                    && $timeWindow->getEndAt() == $expectedTimeWindow->getEndAt();
            });

        $result = $this->rule->process($optimizationState);

        $this->assertSuccessRuleResult($result);
    }

    /**
     * @test
     */
    public function it_does_not_apply_rule_when_feature_flag_is_disabled(): void
    {
        $mockRoute = Mockery::mock(Route::class);
        $officeId = TestValue::OFFICE_ID;

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'officeId' => $officeId,
            'routes' => [$mockRoute],
        ]);

        $this->mockFeatureFlagService->shouldReceive('isFeatureEnabledForOffice')
            ->once()
            ->withSomeOfArgs(self::FEATURE_FLAG)
            ->andReturnFalse();

        $mockRoute
            ->shouldReceive('setTimeWindow')
            ->never();

        $result = $this->rule->process($optimizationState);

        $this->assertTriggeredRuleResult($result);
    }

    protected function getClassRuleName(): string
    {
        return RestrictTimeWindow::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->rule, $this->mockFeatureFlagService);
    }
}
