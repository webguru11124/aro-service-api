<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\OptimizationRules\AddExtraTimeToGetToFirstLocation;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Traits\AssertRuleExecutionResultsTrait;
use Tests\Traits\RuleGetNameAndDescription;

class AddExtraTimeToGetToFirstLocationTest extends TestCase
{
    use AssertRuleExecutionResultsTrait;
    use RuleGetNameAndDescription;

    private const WORKING_DAY_START = '08:00:00';
    private const WORKING_DAY_HOURS = 8;
    private const EXTRA_MINUTES = 15;

    private AddExtraTimeToGetToFirstLocation $rule;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('aptive.travel_time_to_first_location', self::EXTRA_MINUTES);

        $this->rule = new AddExtraTimeToGetToFirstLocation();
    }

    /**
     * @test
     */
    public function it_applies_rule_correctly(): void
    {
        $workingDayTimeWindow = new TimeWindow(
            Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START),
            Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START)->addHours(self::WORKING_DAY_HOURS),
        );
        $extendedTimeWindow = new TimeWindow(
            $workingDayTimeWindow->getStartAt()->clone()->subMinutes(self::EXTRA_MINUTES),
            $workingDayTimeWindow->getEndAt()->clone()->addMinutes(self::EXTRA_MINUTES),
        );

        $mockRoute = Mockery::mock(Route::class);
        $mockRoute->shouldReceive('getTimeWindow')
            ->once()
            ->andReturn($workingDayTimeWindow);
        $mockRoute->shouldReceive('setTimeWindow')
            ->once()
            ->withArgs(function (TimeWindow $timeWindow) use ($extendedTimeWindow) {
                return $timeWindow->getStartAt() == $extendedTimeWindow->getStartAt()
                    && $timeWindow->getEndAt() == $extendedTimeWindow->getEndAt();
            });

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$mockRoute],
        ]);

        $result = $this->rule->process($optimizationState);

        $this->assertSuccessRuleResult($result);
    }

    protected function getClassRuleName(): string
    {
        return AddExtraTimeToGetToFirstLocation::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->rule);
        Mockery::close();
    }
}
