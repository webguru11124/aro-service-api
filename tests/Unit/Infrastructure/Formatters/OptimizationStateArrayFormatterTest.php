<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Formatters;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Domain\SharedKernel\Entities\Office;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Infrastructure\Formatters\OptimizationStateArrayFormatter;
use App\Infrastructure\Formatters\WeatherInfoArrayFormatter;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\Factories\OptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\RouteOptimization\RuleExecutionResultFactory;
use Tests\Tools\Factories\TotalWeightedServiceMetricFactory;
use Tests\Tools\Factories\WeatherInfoFactory;
use Tests\Tools\TestValue;

class OptimizationStateArrayFormatterTest extends TestCase
{
    private OptimizationStateArrayFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter = new OptimizationStateArrayFormatter(new WeatherInfoArrayFormatter());
    }

    /**
     * @test
     */
    public function it_can_format_an_optimization_state_as_an_array(): void
    {
        $now = Carbon::now();
        $unassignedAppointment = AppointmentFactory::make();
        $weatherInfo = WeatherInfoFactory::make();

        /** @var Office $office */
        $office = OfficeFactory::make();
        /** @var OptimizationState $optimizationState */
        $optimizationState = OptimizationStateFactory::make([
            'office' => $office,
            'status' => OptimizationStatus::PRE,
            'engine' => OptimizationEngine::VROOM,
            'createdAt' => $now,
            'timeFrame' => new TimeWindow(
                $now,
                $now->clone()->addHour()
            ),
            'routes' => [],
            'weatherInfo' => $weatherInfo,
            'unassignedAppointments' => [$unassignedAppointment],
        ]);

        $ruleExecutionResults = RuleExecutionResultFactory::make();
        $optimizationState->addRuleExecutionResults(collect([$ruleExecutionResults]));

        $expectedFormat = [
            'state' => [
                'engine' => OptimizationEngine::VROOM->value,
                'optimization_window_start' => $now->toDateTimeString(),
                'optimization_window_end' => $now->clone()->addHour()->toDateTimeString(),
                'created_at' => $now->timestamp,
                'unassigned_appointments' => [
                    ['id' => $unassignedAppointment->getId()],
                ],
                'params' => [
                    'simulation_run' => false,
                    'build_planned_optimization' => false,
                    'last_optimization_run' => false,
                ],
            ],
            'metrics' => [],
            'weather' => [
                'condition' => $weatherInfo->getWeatherCondition()->getMain(),
                'is_inclement' => $weatherInfo->getWeatherCondition()->isInclement(),
                'wind' => $weatherInfo->getWind()->direction . ' ' . $weatherInfo->getWind()->speed,
                'temperature' => $weatherInfo->getTemperature()->temp,
                'pressure' => $weatherInfo->getPressure(),
                'humidity' => $weatherInfo->getHumidity(),
            ],
            'rules' => [
                [
                    'id' => $ruleExecutionResults->getRuleId(),
                    'name' => $ruleExecutionResults->getRuleName(),
                    'description' => $ruleExecutionResults->getRuleDescription(),
                    'is_triggered' => $ruleExecutionResults->isTriggered(),
                    'is_applied' => $ruleExecutionResults->isApplied(),
                ],
            ],
            'office' => [
                'office_id' => $office->getId(),
                'office' => $office->getName(),
            ],
        ];

        $formattedState = $this->formatter->format($optimizationState);

        $this->assertIsArray($formattedState);
        $this->assertEquals($expectedFormat, $formattedState);
    }

    /**
     * @test
     */
    public function it_can_format_an_empty_optimization_state_as_an_array(): void
    {
        $now = Carbon::now();

        /** @var Office $office */
        $office = OfficeFactory::make();
        /** @var OptimizationState $optimization */
        $optimization = OptimizationStateFactory::make([
            'office' => $office,
            'unassignedAppointments' => new Collection(),
            'status' => OptimizationStatus::PRE,
            'engine' => OptimizationEngine::VROOM,
            'routes' => new Collection(),
            'createdAt' => $now,
            'timeFrame' => new TimeWindow(
                $now,
                $now->clone()->addHour()
            ),
            'optimizationParams' => new OptimizationParams(true, true, true),
        ]);

        $expectedFormat = [
            'state' => [
                'params' => [
                    'simulation_run' => true,
                    'build_planned_optimization' => true,
                    'last_optimization_run' => true,
                ],
                'engine' => OptimizationEngine::VROOM->value,
                'optimization_window_start' => $now->toDateTimeString(),
                'optimization_window_end' => $now->clone()->addHour()->toDateTimeString(),
                'created_at' => $now->timestamp,
                'unassigned_appointments' => [],
            ],
            'metrics' => [],
            'weather' => [],
            'rules' => [],
            'office' => [
                'office_id' => $office->getId(),
                'office' => $office->getName(),
            ],
        ];

        $formattedState = $this->formatter->format($optimization);

        $this->assertIsArray($formattedState);
        $this->assertEquals($expectedFormat, $formattedState);
    }

    /**
     * @test
     */
    public function it_formats_average_metric(): void
    {
        $formattedState = $this->formatter->format(OptimizationStateFactory::make([
            'routes' => [
                RouteFactory::make(['metrics' => [TotalWeightedServiceMetricFactory::make(['value' => 10])]]),
                RouteFactory::make(['metrics' => [TotalWeightedServiceMetricFactory::make(['value' => 11])]]),
                RouteFactory::make(['metrics' => [TotalWeightedServiceMetricFactory::make(['value' => 12])]]),
                RouteFactory::make(['metrics' => [TotalWeightedServiceMetricFactory::make(['value' => 13])]]),
                RouteFactory::make(['metrics' => [TotalWeightedServiceMetricFactory::make(['value' => 14])]]),
            ],
        ]));

        $this->assertIsArray($formattedState);

        $expectedMetrics = [
            'optimization_score' => 0.86,
            'total_weighted_services' => 4.29,
        ];
        $this->assertEquals($expectedMetrics, $formattedState['metrics']);
    }

    /**
     * @test
     */
    public function it_formats_reschedule_route_and_unassigned_appointments(): void
    {
        $formattedState = $this->formatter->format(OptimizationStateFactory::make([
            'routes' => [],
            'unassignedAppointments' => [
                AppointmentFactory::make([
                    'id' => TestValue::APPOINTMENT_ID + 1,
                ]),
            ],
        ]));

        $this->assertIsArray($formattedState);
        $this->assertEquals(TestValue::APPOINTMENT_ID + 1, $formattedState['state']['unassigned_appointments'][0]['id']);
    }
}
