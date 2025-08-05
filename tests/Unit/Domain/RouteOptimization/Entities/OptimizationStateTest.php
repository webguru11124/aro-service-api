<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\Entities;

use Tests\Tools\TestValue;
use Tests\Tools\Factories\WeatherInfoFactory;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Enums\MetricKey;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\OptimizationRules\MustConsiderRoadTraffic;
use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Average;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;
use Mockery;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\RouteOptimization\MeetingFactory;
use Tests\Tools\Factories\TotalWeightedServiceMetricFactory;
use Illuminate\Support\Collection;

class OptimizationStateTest extends TestCase
{
    /**
     * @test
     *
     * ::getAllAppointments
     */
    public function it_returns_all_appointments(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make();
        $allTestAppointments = collect();

        foreach ($optimizationState->getRoutes() as $route) {
            $allTestAppointments = $allTestAppointments->merge($route->getAppointments());
        }

        $allTestAppointments = $allTestAppointments->merge($optimizationState->getUnassignedAppointments());

        $this->assertEquals(count($allTestAppointments), count($optimizationState->getAllAppointments()));
        $this->assertEquals($optimizationState->getAllAppointments()->toArray(), $allTestAppointments->toArray());
    }

    /**
     * @test
     *
     * ::getAreaCentralPoint
     */
    public function it_returns_area_central_point(): void
    {
        $latitude = $this->faker->latitude(TestValue::MIN_LATITUDE, TestValue::MAX_LATITUDE);
        $longitude = $this->faker->longitude(TestValue::MIN_LONGITUDE, TestValue::MAX_LONGITUDE);

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [
                RouteFactory::make([
                    'workEvents' => [
                        AppointmentFactory::make([
                            'location' => new Coordinate(
                                $latitude,
                                $longitude,
                            ),
                        ]),
                    ],
                ]),
            ],
            'unassignedAppointments' => [],
        ]);

        $centralPoint = $optimizationState->getAreaCentralPoint();
        $this->assertNotNull($centralPoint);
        $this->assertSame($longitude, $centralPoint->getLongitude());
        $this->assertSame($latitude, $centralPoint->getLatitude());
    }

    /**
     * @test
     *
     * ::getAreaCentralPoint
     */
    public function it_returns_null_when_there_are_no_appointments(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [
                RouteFactory::make([
                    'workEvents' => [],
                ]),
            ],
            'unassignedAppointments' => [],
        ]);

        $centralPoint = $optimizationState->getAreaCentralPoint();
        $this->assertNull($centralPoint);
    }

    /**
     * @test
     *
     * ::getWeatherInfo
     */
    public function it_gets_weather_info_correctly(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make();

        $weatherInfo = WeatherInfoFactory::make();
        $optimizationState->setWeatherInfo($weatherInfo);

        $this->assertEquals($weatherInfo, $optimizationState->getWeatherInfo());
    }

    /**
     * @test
     *
     * ::getAssignedAppointments
     */
    public function it_returns_assigned_appointments(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make();
        $assignedAppointments = new Collection();

        foreach ($optimizationState->getRoutes() as $route) {
            $assignedAppointments = $assignedAppointments->merge($route->getAppointments());
        }

        $this->assertEquals($assignedAppointments->toArray(), $optimizationState->getAssignedAppointments()->toArray());
    }

    /**
     * @test
     *
     * ::getVisitableWorkEvents
     */
    public function it_returns_visitable_work_events(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [
                RouteFactory::make([
                    'workEvents' => [
                        MeetingFactory::make(),
                        AppointmentFactory::make(),
                    ],
                ]),
            ],
            'unassignedAppointments' => [
                AppointmentFactory::make(),
            ],
        ]);

        $this->assertCount(3, $optimizationState->getVisitableWorkEvents());
    }

    /**
     * @test
     *
     * ::getDate
     */
    public function it_returns_optimization_date(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make();
        $date = $optimizationState->getDate();
        $expectedDate = $optimizationState->getOptimizationTimeFrame()->getStartAt()->startOfDay();
        $this->assertEquals($expectedDate, $date);
    }

    /**
     * @test
     *
     * ::getRoutes
     */
    public function set_routes(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make();
        $replacementRoutes = RouteFactory::many(3);
        $optimizationState->setRoutes($replacementRoutes);

        $this->assertEquals($replacementRoutes, $optimizationState->getRoutes()->toArray());
    }

    /**
     * @test
     *
     * ::getUnassignedAppointments
     */
    public function it_gets_unassigned_appointments(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make();
        $replacementAppointments = AppointmentFactory::many(2);
        $optimizationState->setUnassignedAppointments($replacementAppointments);

        $this->assertEquals($replacementAppointments, $optimizationState->getUnassignedAppointments()->toArray());
    }

    /**
     * @test
     *
     * ::getOptimizationEngine
     */
    public function get_optimization_engine(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make();
        $this->assertEquals(OptimizationEngine::VROOM, $optimizationState->getOptimizationEngine());
    }

    /**
     * @test
     *
     * ::getAverageScores
     */
    public function it_gets_average_scores(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [
                RouteFactory::make([
                    'metrics' => [TotalWeightedServiceMetricFactory::make(['value' => 10])],
                ]),
                RouteFactory::make([
                    'metrics' => [TotalWeightedServiceMetricFactory::make(['value' => 8])],
                ]),
                RouteFactory::make([ // route without appointments will be skipped
                    'workEvents' => [],
                    'metrics' => [TotalWeightedServiceMetricFactory::make(['value' => 4])],
                ]),
            ],
        ]);

        $averageScores = $optimizationState->getAverageScores();

        $expectedAverageScores = [
            MetricKey::OPTIMIZATION_SCORE->value => 0.64,
            MetricKey::TOTAL_WEIGHTED_SERVICES->value => 3.22,
        ];

        /** @var Average $average */
        foreach ($averageScores as $average) {
            $this->assertEquals($expectedAverageScores[$average->getKey()->value], $average->getScore()->value());
        }
    }

    /**
     * @test
     *
     * ::getAverageScores
     */
    public function it_returns_no_averages_when_there_are_no_routes_with_appointments(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [
                RouteFactory::make([
                    'workEvents' => [],
                    'metrics' => [TotalWeightedServiceMetricFactory::make(['value' => 8])],
                ]),
                RouteFactory::make([
                    'workEvents' => [],
                    'metrics' => [TotalWeightedServiceMetricFactory::make(['value' => 4])],
                ]),
            ],
        ]);

        $averageScores = $optimizationState->getAverageScores();

        $this->assertCount(0, $averageScores);
    }

    /**
     * @test
     *
     * ::getAverageScores
     */
    public function it_returns_no_averages_when_there_are_no_metrics_calculated_for_routes(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'routes' => [
                RouteFactory::make(),
                RouteFactory::make(),
            ],
        ]);

        $averageScores = $optimizationState->getAverageScores();

        $this->assertCount(0, $averageScores);
    }

    /**
     * @test
     *
     * ::addTriggeredRules
     */
    public function it_adds_triggered_rules(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make();
        $rules = collect([
            new RuleExecutionResult('test_rule', 'test name', 'description', true, true),
        ]);
        $optimizationState->addRuleExecutionResults($rules);

        $this->assertEquals($rules, $optimizationState->getRuleExecutionResults());
    }

    /**
     * @test
     *
     * ::enableRouteTrafficConsideration
     * ::isTrafficConsiderationEnabled
     */
    public function it_enables_traffic_consideration(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make();

        $optimizationState->enableRouteTrafficConsideration();

        $this->assertTrue($optimizationState->isTrafficConsiderationEnabled());
    }

    /**
     * @test
     *
     * ::applyRule
     */
    public function it_applies_optimization_rule(): void
    {
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make();

        $rule = Mockery::mock(MustConsiderRoadTraffic::class)->makePartial();
        $rule->shouldReceive('process')
            ->once()
            ->with($optimizationState)
            ->andReturn(new RuleExecutionResult(
                $rule->id(),
                $rule->name(),
                $rule->description(),
                true,
                true
            ));

        $optimizationState->applyRule($rule);

        /** @var RuleExecutionResult $result */
        $result = $optimizationState->getRuleExecutionResults()->first();

        $this->assertEquals($rule->id(), $result->getRuleId());
        $this->assertEquals($rule->name(), $result->getRuleName());
        $this->assertEquals($rule->description(), $result->getRuleDescription());
        $this->assertTrue($result->isTriggered());
        $this->assertTrue($result->isApplied());
    }

    /**
     * @test
     *
     * ::applyRule
     */
    public function it_does_not_apply_optimization_rule_when_it_is_disabled(): void
    {
        /** @var MustConsiderRoadTraffic|Mockery\MockInterface $rule */
        $rule = Mockery::mock(MustConsiderRoadTraffic::class)->makePartial();
        $rule->shouldReceive('process')
            ->never();

        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'optimizationParams' => new OptimizationParams(disabledRules: [$rule->id()]),
        ]);

        $optimizationState->applyRule($rule);

        /** @var RuleExecutionResult $result */
        $result = $optimizationState->getRuleExecutionResults()->first();

        $this->assertEquals($rule->id(), $result->getRuleId());
        $this->assertEquals($rule->name(), $result->getRuleName());
        $this->assertEquals($rule->description(), $result->getRuleDescription());
        $this->assertFalse($result->isTriggered());
        $this->assertFalse($result->isApplied());
    }
}
