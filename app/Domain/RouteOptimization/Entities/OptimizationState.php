<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Entities;

use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\RouteOptimization\Entities\Weather\WeatherInfo;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Entities\WorkEvent\Meeting;
use App\Domain\RouteOptimization\Enums\MetricKey;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use App\Domain\RouteOptimization\OptimizationRules\GeneralOptimizationRule;
use App\Domain\RouteOptimization\ValueObjects\OptimizationParams;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Average;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Score;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;
use App\Domain\SharedKernel\Entities\Office;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class OptimizationState
{
    private int|null $previousStateId;

    /** @var Collection<Route> */
    private Collection $routes;

    /** @var Collection<Appointment> */
    private Collection $unassignedAppointments;

    /** @var Collection<RuleExecutionResult> */
    private Collection $ruleExecutionResults;

    private bool $trafficConsideration = false;
    private WeatherInfo|null $weatherInfo = null;

    public function __construct(
        private readonly int $id,
        private readonly OptimizationEngine $engine,
        private readonly OptimizationStatus $status,
        private readonly CarbonInterface $createdAt,
        private readonly Office $office,
        private readonly TimeWindow $optimizationTimeFrame,
        private OptimizationParams $optimizationParams,
    ) {
        $this->routes = new Collection();
        $this->unassignedAppointments = new Collection();
        $this->ruleExecutionResults = new Collection();
        $this->previousStateId = null;
    }

    /**
     * Return all appointments for each route and the unassigned appointments
     *
     * @return Collection<Appointment>
     */
    public function getAllAppointments(): Collection
    {
        return $this->getAssignedAppointments()->merge($this->getUnassignedAppointments());
    }

    /**
     * Get the coordinate of central point of the area
     *
     * @return Coordinate|null
     */
    public function getAreaCentralPoint(): Coordinate|null
    {
        $appointments = $this->getAllAppointments();
        $appointmentsCount = $appointments->count();

        if ($appointmentsCount === 0) {
            return null;
        }

        [$totalLatitude, $totalLongitude] = $this->calculateTotalLatLong($appointments);

        return new Coordinate($totalLatitude / $appointmentsCount, $totalLongitude / $appointmentsCount);
    }

    /**
     * @param Collection $appointments
     *
     * @return array<float>
     */
    private function calculateTotalLatLong(Collection $appointments): array
    {
        return $appointments->reduce(function (array $carry, Appointment $appointment) {
            $location = $appointment->getLocation();
            $carry[0] += $location->getLatitude();
            $carry[1] += $location->getLongitude();

            return $carry;
        }, [0, 0]);
    }

    /**
     * Return assigned appointments for each route
     *
     * @return Collection<Appointment>
     */
    public function getAssignedAppointments(): Collection
    {
        return $this->routes->flatMap(function ($route) {
            /** @var Route $route */
            return $route->getAppointments();
        });
    }

    /**
     * Return all appointments and meetings for each route and the unassigned appointments
     *
     * @return Collection<Appointment|Meeting>
     */
    public function getVisitableWorkEvents(): Collection
    {
        $events = new Collection();
        $events = $events->merge($this->unassignedAppointments);

        foreach ($this->getRoutes() as $route) {
            $events = $events->merge($route->getAppointments())->merge($route->getMeetings());
        }

        return $events;
    }

    /**
     * @return CarbonInterface
     */
    public function getCreatedAt(): CarbonInterface
    {
        return $this->createdAt;
    }

    public function getDate(): CarbonInterface
    {
        return $this->optimizationTimeFrame->getStartAt()->startOfDay();
    }

    /**
     * @return OptimizationEngine
     */
    public function getEngine(): OptimizationEngine
    {
        return $this->engine;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getPreviousStateId(): int|null
    {
        return $this->previousStateId;
    }

    /**
     * @param int $id
     *
     * @return OptimizationState
     */
    public function setPreviousStateId(int $id): OptimizationState
    {
        $this->previousStateId = $id;

        return $this;
    }

    /**
     * @return TimeWindow
     */
    public function getOptimizationTimeFrame(): TimeWindow
    {
        return $this->optimizationTimeFrame;
    }

    /**
     * @param Route ...$routes
     *
     * @return OptimizationState
     */
    public function addRoute(Route ...$routes): OptimizationState
    {
        foreach ($routes as $route) {
            $this->routes->add($route);
        }

        return $this;
    }

    /**
     * Returns the optimization engine object
     *
     * @return OptimizationEngine
     */
    public function getOptimizationEngine(): OptimizationEngine
    {
        return $this->engine;
    }

    /**
     * @return Collection<Route>
     */
    public function getRoutes(): Collection
    {
        return $this->routes;
    }

    /**
     * @param Collection<Route>|Route[] $routes
     *
     * @return $this
     */
    public function setRoutes(Collection|array $routes): OptimizationState
    {
        $this->routes = new Collection($routes);

        return $this;
    }

    /**
     * @return OptimizationStatus
     */
    public function getStatus(): OptimizationStatus
    {
        return $this->status;
    }

    /**
     * @param Appointment ...$appointments
     *
     * @return OptimizationState
     */
    public function addUnassignedAppointment(Appointment ...$appointments): OptimizationState
    {
        foreach ($appointments as $appointment) {
            $this->unassignedAppointments->add($appointment);
        }

        return $this;
    }

    /**
     * @return Collection<Appointment>
     */
    public function getUnassignedAppointments(): Collection
    {
        return $this->unassignedAppointments;
    }

    /**
     * @param Collection<Appointment>|Appointment[] $appointments
     *
     * @return OptimizationState
     */
    public function setUnassignedAppointments(Collection|array $appointments): OptimizationState
    {
        $this->unassignedAppointments = (new Collection($appointments))->sortBy(
            fn (Appointment $appointment) => $appointment->getTimeWindow()?->getStartAt()
        );

        return $this;
    }

    /**
     * @return Collection<RuleExecutionResult>
     */
    public function getRuleExecutionResults(): Collection
    {
        return $this->ruleExecutionResults;
    }

    /**
     * @param Collection<RuleExecutionResult> $rules
     */
    public function addRuleExecutionResults(Collection $rules): OptimizationState
    {
        $this->ruleExecutionResults = $this->ruleExecutionResults->merge($rules);

        return $this;
    }

    /**
     * @param WeatherInfo|null $weatherInfo
     *
     * @return $this
     */
    public function setWeatherInfo(WeatherInfo|null $weatherInfo): self
    {
        $this->weatherInfo = $weatherInfo;

        return $this;
    }

    /**
     * @return WeatherInfo|null
     */
    public function getWeatherInfo(): WeatherInfo|null
    {
        return $this->weatherInfo;
    }

    /**
     * @return Office
     */
    public function getOffice(): Office
    {
        return $this->office;
    }

    /**
     * @param Route $route
     *
     * @return $this
     */
    public function updateRoute(Route $route): self
    {
        $routeId = $route->getId();

        $filtered = $this->routes->filter(
            fn (Route $route) => $route->getId() === $routeId
        );

        if ($filtered->isEmpty()) {
            return $this;
        }

        if (spl_object_id($filtered->first()) === spl_object_id($route)) {
            return $this;
        }

        $index = $filtered->keys()->first();

        $this->routes->offsetUnset($index);
        $this->addRoute($route);

        return $this;
    }

    /**
     * @return bool
     */
    public function isLastOptimizationRun(): bool
    {
        return $this->getOptimizationParams()->lastOptimizationRun;
    }

    /**
     * Returns average metric scores
     *
     * @return Collection<Average>
     */
    public function getAverageScores(): Collection
    {
        $averages = new Collection();
        $averageMetricScores = [];

        $routesWithAppointments = $this->getRoutes()->filter(
            fn (Route $route) => $route->getAppointments()->isNotEmpty()
        );

        if ($routesWithAppointments->isEmpty()) {
            return $averages;
        }

        /** @var Route $route */
        $route = $routesWithAppointments->first();

        if ($route->getMetrics()->isEmpty()) {
            return $averages;
        }

        $averageMetricScores[MetricKey::OPTIMIZATION_SCORE->value] = 0;

        foreach ($route->getMetrics() as $metric) {
            $averageMetricScores[$metric->getKey()->value] = 0;
        }

        /** @var Route $route */
        foreach ($routesWithAppointments as $route) {
            foreach ($route->getMetrics() as $metric) {
                if (isset($metric->getKey()->value)) {
                    $averageMetricScores[$metric->getKey()->value] += $metric->getScore()->value();
                }
            }

            $averageMetricScores[MetricKey::OPTIMIZATION_SCORE->value] += $route->getOptimizationScore()->value();
        }

        $metricCount = $routesWithAppointments->count();

        foreach ($averageMetricScores as $key => $value) {
            $averages->add(new Average(
                key: MetricKey::from($key),
                score: new Score($value / $metricCount)
            ));
        }

        return $averages;
    }

    /**
     * It enables the traffic consideration for the optimization
     *
     * @return void
     */
    public function enableRouteTrafficConsideration(): void
    {
        $this->trafficConsideration = true;
    }

    /**
     * It returns the traffic consideration status
     *
     * @return bool
     */
    public function isTrafficConsiderationEnabled(): bool
    {
        return $this->trafficConsideration;
    }

    /**
     * @return OptimizationParams
     */
    public function getOptimizationParams(): OptimizationParams
    {
        return $this->optimizationParams;
    }

    /**
     * @param OptimizationParams $optimizationParams
     *
     * @return self
     */
    public function setOptimizationParams(OptimizationParams $optimizationParams): self
    {
        $this->optimizationParams = $optimizationParams;

        return $this;
    }

    /**
     * @param GeneralOptimizationRule $ruleObject
     *
     * @return OptimizationState
     */
    public function applyRule(GeneralOptimizationRule $ruleObject): OptimizationState
    {
        if ($this->getOptimizationParams()->isRuleDisabled($ruleObject->id())) {
            $this->addRuleExecutionResults(
                new Collection([new RuleExecutionResult(
                    $ruleObject->id(),
                    $ruleObject->name(),
                    $ruleObject->description()
                )])
            );

            return $this;
        }

        $result = $ruleObject->process($this);
        $this->ruleExecutionResults->add($result);

        return $this;
    }
}
