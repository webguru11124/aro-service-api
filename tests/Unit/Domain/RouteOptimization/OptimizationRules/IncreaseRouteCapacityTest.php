<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\OptimizationRules\IncreaseRouteCapacity;
use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Domain\RouteOptimization\ValueObjects\RouteType;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Traits\AssertRuleExecutionResultsTrait;
use Tests\Traits\RuleGetNameAndDescription;

class IncreaseRouteCapacityTest extends TestCase
{
    use AssertRuleExecutionResultsTrait;
    use RuleGetNameAndDescription;

    private const ROUTE_CAPACITY = 15;
    public const MAX_CAPACITY_REGULAR_ROUTE = 18;
    public const MAX_CAPACITY_EXTENDED_ROUTE = 21;
    public const MAX_CAPACITY_SHORT_ROUTE = 12;
    public const RESERVED_SPOTS_FOR_SHORT_ROUTES = 5;
    public const RESERVED_SPOTS_DEFAULT = 6;

    private IncreaseRouteCapacity $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new IncreaseRouteCapacity();
    }

    /**
     * @test
     */
    public function it_applies_rule_correctly(): void
    {
        $route = RouteFactory::make([
            'workEvents' => [],
            'actualCapacityCount' => 21,
            'capacity' => self::ROUTE_CAPACITY,
        ]);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route],
        ]);

        $this->rule->process($optimizationState, OptimizationStateFactory::make());

        /** @var Route $resultRoute */
        $resultRoute = $optimizationState->getRoutes()->first();
        $this->assertGreaterThan(self::ROUTE_CAPACITY, $resultRoute->getCapacity());

        $this->assertSuccessRuleResult($optimizationState->getRuleExecutionResults()->first());
    }

    /**
     * @test
     */
    public function it_does_not_increase_capacity_when_there_no_unassigned_appointments(): void
    {
        $route = RouteFactory::make([
            'workEvents' => [],
            'actualCapacityCount' => 21,
            'capacity' => self::ROUTE_CAPACITY,
        ]);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route],
            'unassignedAppointments' => [],
        ]);

        $this->rule->process($optimizationState, OptimizationStateFactory::make([
            'unassignedAppointments' => [],
        ]));

        /** @var Route $resultRoute */
        $resultRoute = $optimizationState->getRoutes()->first();
        $this->assertEquals(self::ROUTE_CAPACITY, $resultRoute->getCapacity());

        $this->assertSuccessRuleResult($optimizationState->getRuleExecutionResults()->first());
    }

    /**
     * @test
     */
    public function it_does_not_increase_capacity_more_than_max_allowed(): void
    {
        $route = RouteFactory::make([
            'workEvents' => [],
            'routeType' => RouteType::REGULAR_ROUTE,
            'capacity' => self::MAX_CAPACITY_REGULAR_ROUTE,
            'actualCapacityCount' => 21,
        ]);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route],
        ]);

        $this->rule->process($optimizationState, OptimizationStateFactory::make());

        /** @var Route $resultRoute */
        $resultRoute = $optimizationState->getRoutes()->first();

        $this->assertEquals(self::MAX_CAPACITY_REGULAR_ROUTE, $resultRoute->getCapacity());
    }

    /**
     * @test
     */
    public function it_increases_capacity_by_one_for_each_unassigned_appointment(): void
    {
        $route01 = RouteFactory::make([
            'workEvents' => [],
            'capacity' => 15,
        ]);
        $route02 = RouteFactory::make([
            'workEvents' => [],
            'capacity' => 14,
        ]);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route01, $route02],
        ]);

        $resultOptimizationState = OptimizationStateFactory::make([
            'unassignedAppointments' => [AppointmentFactory::make()],
        ]);

        $this->rule->process($optimizationState, $resultOptimizationState);

        $resultRoutes = $optimizationState->getRoutes();
        $resultRoutes->each(function (Route $route) {
            $this->assertEquals(15, $route->getCapacity());
        });

        $this->assertSuccessRuleResult($optimizationState->getRuleExecutionResults()->first());
    }

    /**
     * @test
     */
    public function it_does_not_increase_capacity_more_than_max_allowed_for_each_unassigned_appointment(): void
    {
        $route01 = RouteFactory::make([
            'workEvents' => [],
            'capacity' => self::MAX_CAPACITY_REGULAR_ROUTE,
            'routeType' => RouteType::REGULAR_ROUTE,
            'actualCapacityCount' => 21,
        ]);
        $route02 = RouteFactory::make([
            'workEvents' => [],
            'capacity' => self::MAX_CAPACITY_REGULAR_ROUTE - 1,
            'routeType' => RouteType::REGULAR_ROUTE,
            'actualCapacityCount' => 21,
        ]);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route01, $route02],
        ]);

        $resultOptimizationState = OptimizationStateFactory::make([
            'unassignedAppointments' => AppointmentFactory::many(2),
        ]);

        $this->rule->process($optimizationState, $resultOptimizationState);

        $resultRoutes = $optimizationState->getRoutes();
        $resultRoutes->each(function (Route $route) {
            $this->assertEquals(self::MAX_CAPACITY_REGULAR_ROUTE, $route->getCapacity());
        });

        $this->assertSuccessRuleResult($optimizationState->getRuleExecutionResults()->first());
    }

    /**
     * @test
     */
    public function it_does_not_apply_rule_when_it_is_disabled(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'optimizationParams' => new OptimizationParams(disabledRules: ['IncreaseRouteCapacity']),
        ]);

        $this->rule->process($optimizationState, OptimizationStateFactory::make());

        $this->assertSkippedRuleResult($optimizationState->getRuleExecutionResults()->first());
    }

    protected function getClassRuleName(): string
    {
        return IncreaseRouteCapacity::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->rule);
    }
}
