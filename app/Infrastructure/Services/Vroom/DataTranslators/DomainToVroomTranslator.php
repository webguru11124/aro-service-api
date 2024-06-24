<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\DataTranslators;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\Entities\WorkEvent\ReservedTime;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Infrastructure\Services\Vroom\DataTranslators\Transformers\AppointmentTransformer;
use App\Infrastructure\Services\Vroom\DataTranslators\Transformers\CoordinateTransformer;
use App\Infrastructure\Services\Vroom\DataTranslators\Transformers\MeetingTransformer;
use App\Infrastructure\Services\Vroom\DataTranslators\Transformers\ReservedTimeTransformer;
use App\Infrastructure\Services\Vroom\DataTranslators\Transformers\SkillTransformer;
use App\Infrastructure\Services\Vroom\DataTranslators\Transformers\TimeWindowTransformer;
use App\Infrastructure\Services\Vroom\DataTranslators\Transformers\WorkBreakTransformer;
use App\Infrastructure\Services\Vroom\DTO\Capacity;
use App\Infrastructure\Services\Vroom\DTO\Job;
use App\Infrastructure\Services\Vroom\DTO\Skills;
use App\Infrastructure\Services\Vroom\DTO\Vehicle;
use App\Infrastructure\Services\Vroom\DTO\VroomBreak;
use App\Infrastructure\Services\Vroom\DTO\VroomEngineOption;
use App\Infrastructure\Services\Vroom\DTO\VroomInputData;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DomainToVroomTranslator
{
    public function __construct(
        private AppointmentTransformer $appointmentTransformer,
        private MeetingTransformer $meetingTransformer,
        private SkillTransformer $skillTransformer,
        private WorkBreakTransformer $workBreakTransformer,
        private TimeWindowTransformer $timeWindowTransformer,
        private CoordinateTransformer $coordinateTransformer,
        private ReservedTimeTransformer $reservedTimeTransformer,
    ) {
    }

    /**
     * @param OptimizationState $optimizationState
     *
     * @return VroomInputData
     */
    public function translate(OptimizationState $optimizationState): VroomInputData
    {
        return new VroomInputData(
            $this->aggregateVehicles($optimizationState),
            $this->aggregateJobs($optimizationState),
            $this->getEngineOptions()
        );
    }

    protected function getEngineOptions(): Collection
    {
        return new Collection([VroomEngineOption::GEOMETRY]);
    }

    /**
     * @param Route $route
     *
     * @return VroomInputData
     */
    public function translateSingleRoute(Route $route): VroomInputData
    {
        /** @var Collection<Vehicle> $vehicles */
        $vehicles = new Collection([$this->vehicleFromRoute($route)]);

        return new VroomInputData(
            $vehicles,
            $this->jobsFromRoute($route),
            $this->getEngineOptions(),
        );
    }

    /**
     * @param OptimizationState $optimizationState
     *
     * @return Collection<Job>
     */
    protected function aggregateJobs(OptimizationState $optimizationState): Collection
    {
        $allJobs = new Collection();
        foreach ($optimizationState->getRoutes() as $route) {
            $routeJobs = $this->jobsFromRoute($route);

            $allJobs = $allJobs->merge($routeJobs->all());
        }
        foreach ($optimizationState->getUnassignedAppointments() as $appointment) {
            $allJobs->add($this->appointmentTransformer->transform($appointment));
        }

        return $allJobs;
    }

    /**
     * @param Route $route
     *
     * @return Collection<Job>
     */
    protected function jobsFromRoute(Route $route): Collection
    {
        $jobs = new Collection();

        foreach ($route->getAppointments() as $appointment) {
            $jobs->add($this->appointmentTransformer->transform($appointment));
        }

        foreach ($route->getMeetings() as $meeting) {
            $jobs->add($this->meetingTransformer->transform($meeting));
        }

        return $jobs;
    }

    /**
     * @param OptimizationState $optimizationState
     *
     * @return Collection<Vehicle>
     */
    protected function aggregateVehicles(OptimizationState $optimizationState): Collection
    {
        $vehicles = new Collection();

        /** @var Route $route */
        foreach ($optimizationState->getRoutes() as $route) {
            $vehicles->add($this->vehicleFromRoute($route));
        }

        return $vehicles->filter();
    }

    protected function vehicleFromRoute(Route $route): Vehicle
    {
        return $this->buildVehicle($route);
    }

    protected function addBreaksToVehicle(Route $route, Vehicle $vehicle): void
    {
        $appointmentsNumber = $route->getAppointments()->count();

        foreach ($route->getAllBreaks() as $break) {
            if (!$route->getTimeWindow()->isDateInTimeWindow($break->getExpectedArrival()->getStartAt())) {
                continue;
            }

            $transformedBreak = $break instanceof ReservedTime
                ? $this->getTransformedAdjustedBreak($break, $route->getTimeWindow()->getEndAt())
                : $this->workBreakTransformer->transform($break, $appointmentsNumber);

            $vehicle->addBreak($transformedBreak);
        }
    }

    protected function getTransformedAdjustedBreak(ReservedTime $reservedTime, CarbonInterface $routeEndAt): VroomBreak
    {
        $clonedReservedTime = clone $reservedTime;

        if ($reservedTime->getTimeWindow()->getEndAt()->greaterThan($routeEndAt)) {
            $newReservedTimeEndAt = $routeEndAt->clone()->subMinute();
            $reservedTimeStartAt = $reservedTime->getTimeWindow()->getStartAt();

            $clonedReservedTime->setTimeWindow(new TimeWindow($reservedTimeStartAt, $newReservedTimeEndAt))
                ->setExpectedArrival(new TimeWindow($reservedTimeStartAt, $newReservedTimeEndAt))
                ->setDuration(Duration::fromMinutes($newReservedTimeEndAt->diffInMinutes($reservedTimeStartAt)));
        }

        return $this->reservedTimeTransformer->transform($clonedReservedTime);
    }

    protected function buildVehicle(Route $route): Vehicle|null
    {
        $servicePro = $route->getServicePro();

        $vehicle = new Vehicle(
            id: $route->getId(),
            description: $servicePro->getName(),
            skills: $this->getSkills($servicePro),
            startLocation: $this->coordinateTransformer->transform($route->getStartLocation()->getLocation()),
            endLocation: $this->coordinateTransformer->transform($route->getEndLocation()->getLocation()),
            timeWindow: $this->timeWindowTransformer->transform($route->getTimeWindow()),
            capacity: new Capacity([$route->getCapacity()]),
        );

        $this->addBreaksToVehicle($route, $vehicle);

        return $vehicle;
    }

    protected function getSkills(ServicePro $servicePro): Skills
    {
        $vroomSkills = new Skills();

        foreach ($servicePro->getSkills()->all() as $skill) {
            $vroomSkills->add($this->skillTransformer->transform($skill));
        }

        return $vroomSkills;
    }
}
