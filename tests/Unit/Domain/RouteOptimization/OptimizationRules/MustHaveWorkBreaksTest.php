<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Lunch;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\OptimizationRules\MustHaveWorkBreaks;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\ReservedTimeFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\RouteOptimization\MeetingFactory;
use Tests\Tools\Factories\ServiceProFactory;
use Tests\Traits\AssertRuleExecutionResultsTrait;
use Tests\Traits\RuleGetNameAndDescription;

class MustHaveWorkBreaksTest extends TestCase
{
    use AssertRuleExecutionResultsTrait;
    use RuleGetNameAndDescription;

    private const WORKING_DAY_START = '08:00:00';
    private const WORKING_DAY_HOURS = 8;

    private const WORK_BREAK_DURATION = 20;
    private const WORK_BREAK_START_MINUTES = 100;
    private const WORK_BREAK_END_MINUTES = 160;

    private const LUNCH_DURATION = 35;
    private const LUNCH_START_MINUTES = 200;
    private const LUNCH_END_MINUTES = 250;

    private MustHaveWorkBreaks $rule;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('aptive.work_break_duration', self::WORK_BREAK_DURATION);
        Config::set('aptive.first_work_break_time_window', [self::WORK_BREAK_START_MINUTES, self::WORK_BREAK_END_MINUTES]);
        Config::set('aptive.last_work_break_time_window', [self::WORK_BREAK_START_MINUTES, self::WORK_BREAK_END_MINUTES]);
        Config::set('aptive.lunch_duration', self::LUNCH_DURATION);
        Config::set('aptive.lunch_time_window', [self::LUNCH_START_MINUTES, self::LUNCH_END_MINUTES]);

        $this->rule = new MustHaveWorkBreaks();
    }

    /**
     * @test
     */
    public function it_skips_adding_work_breaks_if_route_has_reserved_time(): void
    {
        $reservedTime = ReservedTimeFactory::make([
            'timeWindow' => new TimeWindow(
                Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START)->addMinutes(self::WORK_BREAK_START_MINUTES),
                Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START)->addMinutes(self::WORK_BREAK_END_MINUTES),
            ),
        ]);

        $servicePro = ServiceProFactory::make([
            'workingHours' => new TimeWindow(
                Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START),
                Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START)->addHours(self::WORKING_DAY_HOURS),
            ),
        ]);

        $route = RouteFactory::make([
            'workEvents' => [],
            'servicePro' => $servicePro,
        ]);

        $route->addWorkEvent($reservedTime);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route],
        ]);

        $result = $this->rule->process($optimizationState);

        /** @var Route $resultRoute */
        $resultRoute = $optimizationState->getRoutes()->first();
        $workBreakIds = $resultRoute->getWorkBreaks()->map(fn (WorkBreak $workBreak) => $workBreak->getId())->toArray();

        $this->assertNotContains(MustHaveWorkBreaks::FIRST_BREAK_ID, $workBreakIds);
        $this->assertSuccessRuleResult($result);
    }

    /**
     * @test
     */
    public function it_skips_adding_work_breaks_if_route_has_fixed_meeting(): void
    {
        $meetingStartAt = Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START)->addMinutes(self::WORK_BREAK_START_MINUTES);
        $meeting = MeetingFactory::make([
            'timeWindow' => new TimeWindow(
                $meetingStartAt,
                $meetingStartAt->clone()->addHour(),
            ),
        ]);

        $servicePro = ServiceProFactory::make([
            'workingHours' => new TimeWindow(
                Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START),
                Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START)->addHours(self::WORKING_DAY_HOURS),
            ),
        ]);

        $route = RouteFactory::make([
            'workEvents' => [],
            'servicePro' => $servicePro,
        ]);

        $route->addWorkEvent($meeting);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route],
        ]);

        $result = $this->rule->process($optimizationState);

        /** @var Route $resultRoute */
        $resultRoute = $optimizationState->getRoutes()->first();
        $workBreakIds = $resultRoute->getWorkBreaks()->map(fn (WorkBreak $workBreak) => $workBreak->getId())->toArray();

        $this->assertNotContains(MustHaveWorkBreaks::FIRST_BREAK_ID, $workBreakIds);
        $this->assertSuccessRuleResult($result);
    }

    /**
     * @test
     */
    public function it_applies_rule_correctly(): void
    {
        $servicePro = ServiceProFactory::make([
            'workingHours' => new TimeWindow(
                Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START),
                Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START)->addHours(self::WORKING_DAY_HOURS),
            ),
        ]);

        $route = RouteFactory::make([
            'workEvents' => [],
            'servicePro' => $servicePro,
            'capacity' => 8,
        ]);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route],
        ]);

        $result = $this->rule->process($optimizationState);

        /** @var Route $resultRoute */
        $resultRoute = $optimizationState->getRoutes()->first();
        $workBreaks = $resultRoute->getWorkBreaks()->toArray();

        /** @var WorkBreak $firstBreak */
        $firstBreak = $workBreaks[0];
        $this->assertEquals(MustHaveWorkBreaks::FIRST_BREAK_ID, $firstBreak->getId());
        $this->assertEquals(self::WORK_BREAK_DURATION, $firstBreak->getDuration()->getTotalMinutes());

        $expectedTimeWindow = new TimeWindow(
            Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START)->addMinutes(self::WORK_BREAK_START_MINUTES),
            Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START)->addMinutes(self::WORK_BREAK_END_MINUTES),
        );
        $this->assertEquals($expectedTimeWindow, $firstBreak->getExpectedArrival());
        $this->assertNull($firstBreak->getTimeWindow());

        /** @var Lunch $lunch */
        $lunch = $workBreaks[1];
        $this->assertEquals(MustHaveWorkBreaks::LUNCH_BREAK_ID, $lunch->getId());
        $this->assertEquals(self::LUNCH_DURATION, $lunch->getDuration()->getTotalMinutes());

        $expectedTimeWindow = new TimeWindow(
            Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START)->addMinutes(self::LUNCH_START_MINUTES),
            Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START)->addMinutes(self::LUNCH_END_MINUTES),
        );
        $this->assertEquals($expectedTimeWindow, $lunch->getExpectedArrival());
        $this->assertNull($lunch->getTimeWindow());

        /** @var WorkBreak $secondBreak */
        $secondBreak = $workBreaks[2];
        $this->assertEquals(MustHaveWorkBreaks::SECOND_BREAK_ID, $secondBreak->getId());
        $this->assertEquals(self::WORK_BREAK_DURATION, $secondBreak->getDuration()->getTotalMinutes());

        $expectedTimeWindow = new TimeWindow(
            Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START)->addMinutes(self::WORK_BREAK_START_MINUTES),
            Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START)->addMinutes(self::WORK_BREAK_END_MINUTES),
        );
        $this->assertEquals($expectedTimeWindow, $secondBreak->getExpectedArrival());
        $this->assertNull($secondBreak->getTimeWindow());
        $this->assertSuccessRuleResult($result);
    }

    /**
     * @test
     *
     * @dataProvider capacityProvider
     */
    public function it_adds_breaks_according_to_capacity(int $capacity, int $numberOfBreaks): void
    {
        $servicePro = ServiceProFactory::make([
            'workingHours' => new TimeWindow(
                Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START),
                Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START)->addHours(self::WORKING_DAY_HOURS),
            ),
        ]);

        $route = RouteFactory::make([
            'workEvents' => [],
            'servicePro' => $servicePro,
            'capacity' => $capacity,
        ]);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route],
        ]);

        $result = $this->rule->process($optimizationState);

        /** @var Route $resultRoute */
        $resultRoute = $optimizationState->getRoutes()->first();
        $workBreaks = $resultRoute->getWorkBreaks();

        $this->assertCount($numberOfBreaks, $workBreaks);
        $this->assertSuccessRuleResult($result);
    }

    public static function capacityProvider(): array
    {
        return [
            'capacity is 3, no breaks' => [3, 0],
            'capacity is 5, one break' => [5, 1],
            'capacity is 7, one break and one lunch' => [7, 2],
            'capacity is 8, two breaks and one lunch' => [8, 3],
        ];
    }

    protected function getClassRuleName(): string
    {
        return MustHaveWorkBreaks::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->rule);
    }
}
