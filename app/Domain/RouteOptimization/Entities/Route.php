<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Entities;

use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Entities\WorkEvent\Lunch;
use App\Domain\RouteOptimization\Entities\WorkEvent\Meeting;
use App\Domain\RouteOptimization\Entities\WorkEvent\ReservedTime;
use App\Domain\RouteOptimization\Entities\WorkEvent\Travel;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkEvent;
use App\Domain\RouteOptimization\Enums\MetricKey;
use App\Domain\RouteOptimization\ValueObjects\EndLocation;
use App\Domain\RouteOptimization\ValueObjects\RouteConfig;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Metric;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Score;
use App\Domain\RouteOptimization\ValueObjects\RouteType;
use App\Domain\RouteOptimization\ValueObjects\StartLocation;
use App\Domain\RouteOptimization\ValueObjects\WorkEvent\ExtraWork;
use App\Domain\RouteOptimization\ValueObjects\WorkEvent\Waiting;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class Route
{
    private const RESCHEDULE_ROUTE_EMPLOYEE_NAME = '#Reschedule Route#';

    /** @var Collection<WorkEvent>  */
    private Collection $workEvents;

    /** @var Collection<Metric>  */
    private Collection $metrics;
    private string|null $geometry = null;
    private int|null $capacity = null;
    private RouteConfig $config;

    public function __construct(
        private int $id,
        private int $officeId,
        private CarbonInterface $date,
        private readonly ServicePro $servicePro,
        private RouteType $routeType,
        private int $actualCapacityCount,
        RouteConfig|null $config = null,
    ) {
        $this->workEvents = new Collection();
        $this->metrics = new Collection();
        $this->config = $config ?? new RouteConfig();
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $this->workEvents = $this->workEvents->map(
            fn (WorkEvent $workEvent) => clone $workEvent
        );

        $this->metrics = $this->metrics->map(
            fn (Metric $metric) => clone $metric
        );
    }

    /**
     * Get the ID of the route
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getOfficeId(): int
    {
        return $this->officeId;
    }

    /**
     * @return CarbonInterface
     */
    public function getDate(): CarbonInterface
    {
        return $this->date;
    }

    /**
     * @return RouteType
     */
    public function getRouteType(): RouteType
    {
        return $this->routeType;
    }

    /**
     * Returns the maximum capacity of the route based on the route type
     *
     * @return int
     */
    public function getMaxCapacity(): int
    {
        if ($this->getServicePro()->getSkillsWithoutPersonal()->isEmpty()) {
            return 0;
        }

        $reserved = $this->getConfig()->getInsideSales()
            + $this->getConfig()->getSummary()
            + $this->getConfig()->getBreaks();
        $maxAvailableCapacity = $this->actualCapacityCount - $reserved;

        return max($maxAvailableCapacity, 0);
    }

    /**
     * Returns the actual spots count of the route
     *
     * @return int
     */
    public function getActualCapacityCount(): int
    {
        return $this->actualCapacityCount;
    }

    /**
     * Adds metric to route
     *
     * @param Metric $metric
     *
     * @return self
     */
    public function setMetric(Metric $metric): self
    {
        $this->metrics->put($metric->getKey()->value, $metric);

        return $this;
    }

    /**
     * Checks if route has specified metric
     *
     * @param MetricKey $key
     *
     * @return bool
     */
    public function hasMetric(MetricKey $key): bool
    {
        return $this->metrics->has($key->value);
    }

    /**
     * Returns metric by key
     *
     * @param MetricKey $key
     *
     * @return Metric
     */
    public function getMetric(MetricKey $key): Metric
    {
        return $this->metrics->get($key->value);
    }

    /**
     * Returns all metrics
     *
     * @return Collection<Metric>
     */
    public function getMetrics(): Collection
    {
        return $this->metrics;
    }

    /**
     * Calculates the overall route optimization score for the route
     *
     * @return Score
     */
    public function getOptimizationScore(): Score
    {
        $totalWeightedScore = 0;
        $totalPossibleWeightedScore = 0;

        foreach ($this->metrics as $metric) {
            $totalWeightedScore += $metric->getWeightedScore();
            $totalPossibleWeightedScore += $metric->getMaxPossibleWeightedScore();
        }

        $scoreValue = $totalPossibleWeightedScore > 0 ? $totalWeightedScore / $totalPossibleWeightedScore : 0;

        return new Score($scoreValue);
    }

    /**
     * Get all events that are Appointment
     *
     * @return Collection<Appointment>
     */
    public function getAppointments(): Collection
    {
        return $this->workEvents
            ->filter(fn (WorkEvent $workEvent) => $workEvent instanceof Appointment)
            ->values();
    }

    /**
     * Get all the meetings
     *
     * @return Collection
     */
    public function getMeetings(): Collection
    {
        return $this->workEvents
            ->filter(fn (WorkEvent $workEvent) => $workEvent instanceof Meeting)
            ->values();
    }

    /**
     * Get all the lunch event
     *
     * @return Collection<int|string, Lunch>
     */
    public function getLunch(): Collection
    {
        return $this->workEvents->filter(fn (WorkEvent $workEvent) => $workEvent instanceof Lunch);
    }

    /**
     * Get all the Travel events of the route
     *
     * @return Collection<Travel>
     */
    public function getTravelEvents(): Collection
    {
        return $this->workEvents
            ->filter(fn (WorkEvent $workEvent) => $workEvent instanceof Travel)
            ->values();
    }

    /**
     * @return ServicePro
     */
    public function getServicePro(): ServicePro
    {
        return $this->servicePro;
    }

    /**
     * Get all events that are WorkBreak
     *
     * @return Collection<WorkBreak>
     */
    public function getWorkBreaks(): Collection
    {
        return $this->workEvents
            ->filter(fn (WorkEvent $workEvent) => $workEvent instanceof WorkBreak)
            ->values();
    }

    /**
     * @return Collection<WorkBreak|ReservedTime>
     */
    public function getAllBreaks(): Collection
    {
        return $this->getWorkBreaks()->merge($this->getReservedTimes());
    }

    /**
     * @return Collection<ReservedTime>
     */
    public function getReservedTimes(): Collection
    {
        return $this->workEvents
            ->filter(fn (WorkEvent $workEvent) => $workEvent instanceof ReservedTime)
            ->values();
    }

    /**
     * @return Collection<WorkEvent>
     */
    public function getWorkEvents(): Collection
    {
        return $this->workEvents;
    }

    /**
     * @return Route
     */
    public function clearWorkEvents(): self
    {
        $this->workEvents = new Collection();

        return $this;
    }

    /**
     * @param WorkEvent $workEvent
     *
     * @return Route
     */
    public function addWorkEvent(WorkEvent $workEvent): self
    {
        if (method_exists($workEvent, 'setRouteId')) {
            $workEvent->setRouteId($this->id);
        }

        // TODO: Throw exception if adding second instance of StartLocation/EndLocation

        $this->workEvents->add($workEvent);

        $this->workEvents = $this->workEvents
            ->sort(function (WorkEvent $eventA, WorkEvent $eventB) {
                return $eventA->getTimeWindow()?->getEndAt() !== $eventB->getTimeWindow()?->getEndAt()
                    ? $eventA->getTimeWindow()?->getEndAt() <=> $eventB->getTimeWindow()?->getEndAt()
                    : $eventA->getTimeWindow()?->getStartAt() <=> $eventB->getTimeWindow()?->getStartAt();
            })
            ->values();

        return $this;
    }

    /**
     * Adds multiple WorkEvents to the route
     *
     * @param Collection $workEvents
     *
     * @return Route
     */
    public function addWorkEvents(Collection $workEvents): self
    {
        $workEvents->each(
            function (WorkEvent $event) {
                $this->addWorkEvent($event);
            }
        );

        return $this;
    }

    /**
     * Removes all work breaks
     *
     * @return Route
     */
    public function removeWorkBreaks(): self
    {
        $this->workEvents = $this->workEvents->filter(fn (WorkEvent $workEvent) => !($workEvent instanceof WorkBreak));

        return $this;
    }

    /**
     * Returns StartLocation object, if it does not exist then creates new based on ServicePro data,
     *
     * @return StartLocation
     */
    public function getStartLocation(): StartLocation
    {
        $location = $this->workEvents->filter(fn (WorkEvent $workEvent) => $workEvent instanceof StartLocation)->first();

        if ($location) {
            return $location;
        }

        $location = new StartLocation(
            $this->getServicePro()->getWorkingHours()->getStartAt(),
            $this->getServicePro()->getStartLocation()
        );

        $this->setStartLocation($location);

        return $location;
    }

    /**
     * Returns EndLocation object, if it does not exist then creates new based on ServicePro data,
     *
     * @return EndLocation
     */
    public function getEndLocation(): EndLocation
    {
        $location = $this->workEvents->filter(fn (WorkEvent $workEvent) => $workEvent instanceof EndLocation)->first();

        if ($location) {
            return $location;
        }

        $location = new EndLocation(
            $this->getServicePro()->getWorkingHours()->getEndAt(),
            $this->getServicePro()->getEndLocation()
        );

        $this->setEndLocation($location);

        return $location;
    }

    /**
     * Setups StartLocation
     *
     * @param CarbonInterface $startAt
     * @param Coordinate $location
     *
     * @return self
     */
    public function setupRouteStart(CarbonInterface $startAt, Coordinate $location): self
    {
        return $this->setStartLocation(
            new StartLocation($startAt, $location)
        );
    }

    /**
     * Sets StartLocation coordinates to ServicePro's home
     *
     * @return Route
     */
    public function setStartLocationCoordinatesToServiceProHome(): self
    {
        $this->setStartLocation(new StartLocation(
            $this->getStartLocation()->getTimeWindow()->getStartAt(),
            $this->getServicePro()->getStartLocation(),
        ));

        return $this;
    }

    /**
     * Sets EndLocation coordinates to ServicePro's home
     *
     * @return Route
     */
    public function setEndLocationCoordinatesToServiceProHome(): self
    {
        $this->setEndLocation(new EndLocation(
            $this->getEndLocation()->getTimeWindow()->getStartAt(),
            $this->getServicePro()->getEndLocation(),
        ));

        return $this;
    }

    /**
     * Sets time window by adjusting StartLocation and EndLocation
     *
     * @param TimeWindow $timeWindow
     *
     * @return Route
     */
    public function setTimeWindow(TimeWindow $timeWindow): self
    {
        return $this
            ->setStartAt($timeWindow->getStartAt())
            ->setEndAt($timeWindow->getEndAt());
    }

    /**
     * Returns time window between StartLocation and EndLocation
     *
     * @return TimeWindow
     */
    public function getTimeWindow(): TimeWindow
    {
        return new TimeWindow(
            $this->getStartLocation()->getTimeWindow()->getStartAt(),
            $this->getEndLocation()->getTimeWindow()->getEndAt(),
        );
    }

    /**
     * @param Travel|Waiting $event
     *
     * @return $this
     */
    public function addExtraWorkByTravelOrWaiting(Travel|Waiting $event): self
    {
        $previousAppointment = $this->getPreviousAppointment($event);
        if ($previousAppointment === null) {
            return $this;
        }

        $nextAppointment = $this->getNextAppointment($event);
        if ($nextAppointment === null) {
            return $this;
        }

        $extraWork = new ExtraWork(
            timeWindow: new TimeWindow($event->getTimeWindow()->getStartAt()->clone(), $event->getTimeWindow()->getEndAt()->clone()),
            startLocation: $previousAppointment->getLocation(),
            endLocation: $nextAppointment->getLocation(),
            skills: $this->servicePro->getSkillsWithoutPersonal()
        );

        $this->addWorkEvent($extraWork);

        return $this;
    }

    /**
     * @param WorkEvent $event
     *
     * @return Appointment|null
     */
    public function getNextAppointment(WorkEvent $event): Appointment|null
    {
        $eventIndex = $this->getWorkEventQueueNumber($event);

        if ($eventIndex === false) {
            return null;
        }

        return $this->workEvents
            ->slice($eventIndex + 1)
            ->filter(fn (WorkEvent $event) => $event instanceof Appointment)
            ->first();
    }

    /**
     * @param WorkEvent $event
     *
     * @return Appointment|null
     */
    public function getPreviousAppointment(WorkEvent $event): Appointment|null
    {
        $eventIndex = $this->getWorkEventQueueNumber($event);

        if ($eventIndex === false) {
            return null;
        }

        return $this->workEvents
            ->slice(0, $eventIndex)
            ->filter(fn (WorkEvent $event) => $event instanceof Appointment)
            ->last();
    }

    /**
     * @param WorkEvent $workEvent
     *
     * @return self
     */
    public function removeWorkEvent(WorkEvent $workEvent): self
    {
        $index = $this->getWorkEventQueueNumber($workEvent);

        if ($index === false) {
            return $this;
        }

        $this->workEvents->offsetUnset($index);
        $this->workEvents = $this->workEvents->values();

        return $this;
    }

    /**
     * It searches the route for a given WorkEvent and return the corresponding number in the events queue if
     * successful. It returns false if given WorkEvent is not found.
     *
     * @param WorkEvent $searchedEvent
     *
     * @return int|false
     */
    private function getWorkEventQueueNumber(WorkEvent $searchedEvent): int|false
    {
        foreach ($this->workEvents as $i => $workEvent) {
            $searchedId = spl_object_id($searchedEvent);
            $currentId = spl_object_id($workEvent);

            if ($searchedId === $currentId) {
                return $i;
            }
        }

        return false;
    }

    /**
     * @return Collection<Waiting>
     */
    public function getWaitingEvents(): Collection
    {
        return $this->workEvents
            ->filter(fn (WorkEvent $workEvent) => $workEvent instanceof Waiting)
            ->values();
    }

    /**
     * @return Duration
     */
    public function getTotalWaiting(): Duration
    {
        $duration = Duration::fromSeconds(0);

        foreach ($this->getWaitingEvents() as $waitingEvent) {
            $duration = $duration->increase($waitingEvent->getDuration());
        }

        return $duration;
    }

    private function setStartLocation(StartLocation $location): self
    {
        return $this->removeStartLocation()->addWorkEvent($location);
    }

    private function setEndLocation(EndLocation $location): self
    {
        return $this->removeEndLocation()->addWorkEvent($location);
    }

    private function removeStartLocation(): self
    {
        $this->workEvents = $this->workEvents->filter(fn (WorkEvent $workEvent) => !($workEvent instanceof StartLocation));

        return $this;
    }

    private function removeEndLocation(): self
    {
        $this->workEvents = $this->workEvents->filter(fn (WorkEvent $workEvent) => !($workEvent instanceof EndLocation));

        return $this;
    }

    private function setStartAt(CarbonInterface $startAt): self
    {
        return $this->setStartLocation(new StartLocation(
            $startAt,
            $this->getStartLocation()->getLocation(),
        ));
    }

    private function setEndAt(CarbonInterface $endAt): self
    {
        return $this->setEndLocation(new EndLocation(
            $endAt,
            $this->getEndLocation()->getLocation(),
        ));
    }

    /**
     * @return string|null
     */
    public function getGeometry(): string|null
    {
        return $this->geometry;
    }

    /**
     * @param string $geometry
     *
     * @return Route
     */
    public function setGeometry(string $geometry): self
    {
        $this->geometry = $geometry;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRescheduleRoute(): bool
    {
        return $this->getServicePro()->getName() == self::RESCHEDULE_ROUTE_EMPLOYEE_NAME;
    }

    /**
     * @return int
     */
    public function getCapacity(): int
    {
        return $this->capacity ?? $this->getMaxCapacity();
    }

    /**
     * @param int $capacity
     *
     * @return $this
     */
    public function setCapacity(int $capacity): self
    {
        $this->capacity = $capacity;

        return $this;
    }

    /**
     * @param int $num
     *
     * @return void
     */
    public function setNumberOfInsideSales(int $num): void
    {
        $this->config = $this->config->setInsideSales($num);
    }

    /**
     * @return void
     */
    public function enableRouteSummary(): void
    {
        $this->config = $this->config->enableRouteSummary();
    }

    /**
     * @return RouteConfig
     */
    public function getConfig(): RouteConfig
    {
        return $this->config;
    }
}
