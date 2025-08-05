<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\OptimizationRules\MustNotExceedMaxWorkingHours;
use App\Domain\RouteOptimization\ValueObjects\RouteType;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\ServiceProFactory;
use Tests\Traits\AssertRuleExecutionResultsTrait;
use Tests\Traits\RuleGetNameAndDescription;

class MustNotExceedMaxWorkingHoursTest extends TestCase
{
    use AssertRuleExecutionResultsTrait;
    use RuleGetNameAndDescription;

    private const WORKING_DAY_START = '08:00:00';
    private const WORKING_DAY_HOURS = 8;
    private const WORK_BREAK_DURATION = 20;
    private const LUNCH_DURATION = 40;
    private const MAX_WORKING_TIME_SUMMER = 630;
    private const MAX_WORKING_TIME_WINTER = 600;

    private MustNotExceedMaxWorkingHours $rule;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('aptive.work_break_duration', self::WORK_BREAK_DURATION);
        Config::set('aptive.lunch_duration', self::LUNCH_DURATION);
        Config::set('aptive.max_working_time.summer.default', self::MAX_WORKING_TIME_SUMMER);
        Config::set('aptive.max_working_time.summer.extended_routes', self::MAX_WORKING_TIME_SUMMER + 70);
        Config::set('aptive.max_working_time.winter.default', self::MAX_WORKING_TIME_WINTER);
        Config::set('aptive.max_working_time.winter.extended_routes', self::MAX_WORKING_TIME_WINTER + 40);

        $this->rule = new MustNotExceedMaxWorkingHours();
    }

    /**
     * @test
     *
     * @dataProvider dateProvider
     */
    public function it_applies_rule_correctly_on_season(CarbonInterface $date, RouteType $routeType, TimeWindow $expectedTimeWindow): void
    {
        /** @var ServicePro $servicePro */
        $servicePro = ServiceProFactory::make([
            'workingHours' => new TimeWindow(
                Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START),
                Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START)->addHours(self::WORKING_DAY_HOURS),
            ),
        ]);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'timeFrame' => new TimeWindow($date->clone()->startOfDay(), $date->clone()->endOfDay()),
            'routes' => [
                RouteFactory::make([
                    'servicePro' => $servicePro,
                    'workEvents' => [],
                    'routeType' => $routeType,
                ]),
            ],
        ]);

        $result = $this->rule->process($optimizationState);

        /** @var Route $resultRoute */
        $resultRoute = $optimizationState->getRoutes()->first();
        $this->assertEquals($expectedTimeWindow, $resultRoute->getTimeWindow());
        $this->assertSuccessRuleResult($result);
    }

    public static function dateProvider(): iterable
    {
        yield 'Summer - Regular route' => [
            Carbon::parse('2024-04-01'), // April
            RouteType::REGULAR_ROUTE,
            new TimeWindow(
                Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START),
                Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START)
                    ->addMinutes(self::MAX_WORKING_TIME_SUMMER)
                    ->addMinutes(self::WORK_BREAK_DURATION)
                    ->addMinutes(self::WORK_BREAK_DURATION)
                    ->addMinutes(self::LUNCH_DURATION)
            ),
        ];

        yield 'Summer - Extended route' => [
            Carbon::parse('2024-10-01'), // October
            RouteType::EXTENDED_ROUTE,
            new TimeWindow(
                Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START),
                Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START)
                    ->addMinutes(self::MAX_WORKING_TIME_SUMMER + 70)
                    ->addMinutes(self::WORK_BREAK_DURATION)
                    ->addMinutes(self::WORK_BREAK_DURATION)
                    ->addMinutes(self::LUNCH_DURATION)
            ),
        ];

        yield 'Winter' => [
            Carbon::parse('2024-03-31'), // March
            RouteType::REGULAR_ROUTE,
            new TimeWindow(
                Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START),
                Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START)
                    ->addMinutes(self::MAX_WORKING_TIME_WINTER)
                    ->addMinutes(self::WORK_BREAK_DURATION)
                    ->addMinutes(self::WORK_BREAK_DURATION)
                    ->addMinutes(self::LUNCH_DURATION)
            ),
        ];

        yield 'Winter - Extended route' => [
            Carbon::parse('2024-11-01'), // November
            RouteType::EXTENDED_ROUTE,
            new TimeWindow(
                Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START),
                Carbon::now()->setTimeFromTimeString(self::WORKING_DAY_START)
                    ->addMinutes(self::MAX_WORKING_TIME_WINTER + 40)
                    ->addMinutes(self::WORK_BREAK_DURATION)
                    ->addMinutes(self::WORK_BREAK_DURATION)
                    ->addMinutes(self::LUNCH_DURATION)
            ),
        ];
    }

    protected function getClassRuleName(): string
    {
        return MustNotExceedMaxWorkingHours::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->rule);
    }
}
