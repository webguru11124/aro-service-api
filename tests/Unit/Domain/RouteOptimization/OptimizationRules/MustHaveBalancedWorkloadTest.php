<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\OptimizationRules\MustHaveBalancedWorkload;
use App\Domain\RouteOptimization\ValueObjects\RouteType;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Traits\AssertRuleExecutionResultsTrait;
use Tests\Traits\RuleGetNameAndDescription;

class MustHaveBalancedWorkloadTest extends TestCase
{
    use AssertRuleExecutionResultsTrait;
    use RuleGetNameAndDescription;

    private const RESERVED_SPOTS_DEFAULT = 3;

    private MustHaveBalancedWorkload $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new MustHaveBalancedWorkload();
    }

    /**
     * @test
     */
    public function it_balances_workload_correctly_across_service_pros(): void
    {
        $routes = [
            RouteFactory::make([
                'workEvents' => AppointmentFactory::many(5),
            ]),
            RouteFactory::make([
                'workEvents' => AppointmentFactory::many(20),
            ]),
            RouteFactory::make([
                'workEvents' => AppointmentFactory::many(10),
            ]),
        ];

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => $routes,
            'unassignedAppointments' => AppointmentFactory::many(2),
        ]);

        $result = $this->rule->process($optimizationState);

        $totalCapacity = 0;

        foreach ($optimizationState->getRoutes() as $resultRoute) {
            $capacity = $resultRoute->getCapacity();
            $this->assertTrue(in_array($capacity, [12, 13]));
            $totalCapacity += $capacity;
        }

        $this->assertEquals(37, $totalCapacity);
        $this->assertSuccessRuleResult($result);
    }

    /**
     * @test
     */
    public function it_applies_rule_correctly_without_unassigned_appointments(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [
                RouteFactory::make([
                    'workEvents' => AppointmentFactory::many(5),
                ]),
            ],
            'unassignedAppointments' => [],
        ]);

        $this->rule->process($optimizationState);

        /** @var Route $resultRoute */
        $resultRoute = $optimizationState->getRoutes()->first();
        $this->assertEquals(5, $resultRoute->getCapacity());
    }

    /**
     * @test
     */
    public function it_does_not_increase_capacity_more_than_max_allowed(): void
    {
        $route01 = RouteFactory::make([
            'workEvents' => AppointmentFactory::many(5),
            'routeType' => RouteType::REGULAR_ROUTE,
            'actualCapacityCount' => 5,
        ]);
        $route02 = RouteFactory::make([
            'workEvents' => AppointmentFactory::many(5),
            'routeType' => RouteType::EXTENDED_ROUTE,
            'actualCapacityCount' => 5,
        ]);
        $route03 = RouteFactory::make([
            'workEvents' => AppointmentFactory::many(5),
            'routeType' => RouteType::SHORT_ROUTE,
            'actualCapacityCount' => 5,
        ]);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route01, $route02, $route03],
            'unassignedAppointments' => [],
        ]);

        $this->rule->process($optimizationState);

        /** @var Collection<Route> $resultRoutes */
        $resultRoutes = $optimizationState->getRoutes();
        $route01AssignedCapacity = $resultRoutes->first(fn (Route $route) => $route->getId() === $route01->getId());
        $route02AssignedCapacity = $resultRoutes->first(fn (Route $route) => $route->getId() === $route02->getId());
        $route03AssignedCapacity = $resultRoutes->first(fn (Route $route) => $route->getId() === $route03->getId());

        $this->assertEquals(2, $route01AssignedCapacity->getCapacity());
        $this->assertEquals(2, $route02AssignedCapacity->getCapacity());
        $this->assertEquals(2, $route03AssignedCapacity->getCapacity());
    }

    /**
     * @test
     */
    public function it_does_not_increase_capacity_more_than_actual_spots(): void
    {
        $route01 = RouteFactory::make([
            'workEvents' => AppointmentFactory::many(22 - self::RESERVED_SPOTS_DEFAULT),
            'routeType' => RouteType::REGULAR_ROUTE,
            'actualCapacityCount' => 22,
        ]);
        $route02 = RouteFactory::make([
            'workEvents' => AppointmentFactory::many(24 - self::RESERVED_SPOTS_DEFAULT),
            'routeType' => RouteType::EXTENDED_ROUTE,
            'actualCapacityCount' => 24,
        ]);
        $route03 = RouteFactory::make([
            'workEvents' => AppointmentFactory::many(15 - self::RESERVED_SPOTS_DEFAULT),
            'routeType' => RouteType::SHORT_ROUTE,
            'actualCapacityCount' => 15,
        ]);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [$route01, $route02, $route03],
            'unassignedAppointments' => [],
        ]);

        $this->rule->process($optimizationState);

        /** @var Collection<Route> $resultRoutes */
        $resultRoutes = $optimizationState->getRoutes();
        $route01AssignedCapacity = $resultRoutes->first(fn (Route $route) => $route->getId() === $route01->getId());
        $route02AssignedCapacity = $resultRoutes->first(fn (Route $route) => $route->getId() === $route02->getId());
        $route03AssignedCapacity = $resultRoutes->first(fn (Route $route) => $route->getId() === $route03->getId());

        $this->assertEquals(22 - self::RESERVED_SPOTS_DEFAULT, $route01AssignedCapacity->getCapacity());
        $this->assertEquals(24 - self::RESERVED_SPOTS_DEFAULT, $route02AssignedCapacity->getCapacity());
        $this->assertEquals(15 - self::RESERVED_SPOTS_DEFAULT, $route03AssignedCapacity->getCapacity());
    }

    protected function getClassRuleName(): string
    {
        return MustHaveBalancedWorkload::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->rule);
    }
}
