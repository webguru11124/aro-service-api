<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Entities\WorkEvent;

use App\Domain\RouteOptimization\DomainContext;
use App\Domain\RouteOptimization\Entities\HasRouteId;
use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\Enums\WorkEventType;
use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Domain\RouteOptimization\ValueObjects\ServiceDuration;
use App\Domain\RouteOptimization\ValueObjects\PropertyDetails;
use App\Domain\RouteOptimization\Entities\Weather\WeatherInfo;

class Appointment extends AbstractWorkEvent
{
    use HasRouteId;

    private const APPOINTMENT_PRIORITY_INITIAL = 'initial';
    private const APPOINTMENT_PRIORITY_RESERVICE = 'reservice';
    private const APPOINTMENT_PRIORITY_HALF_DAY = 'half_day';
    private const APPOINTMENT_PRIORITY_DEFAULT = 'default';
    private const APPOINTMENT_PRIORITY_NOTIFIED = 'notified';
    private const APPOINTMENT_PRIORITY_LOCKED = 'locked';

    private const APPOINTMENT_SETUP_INITIAL = 'initial';
    private const APPOINTMENT_SETUP_DEFAULT = 'default';

    private const WEIGHTED_INITIAL = 2;
    private const WEIGHTED_REGULAR = 1;

    private int $priority;
    private Duration $setupDuration;

    /** @var Collection<int|string, Skill>  */
    private Collection $skills;

    private bool $isLocked = false;

    private ServiceDuration|null $serviceDuration = null;

    public function __construct(
        int $id,
        string $description,
        private readonly Coordinate $location,
        private bool $notified,
        private int $officeId,
        private int $customerId,
        private int|null $preferredTechId = null,
        Collection|null $skills = null,
    ) {
        $this->skills = new Collection($skills);
        parent::__construct($id, $description);
    }

    /**
     * @return Coordinate
     */
    public function getLocation(): Coordinate
    {
        return $this->location;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        if (isset($this->priority)) {
            return $this->priority;
        }

        if ($this->isNotified()) {
            $this->priority = DomainContext::getAppointmentPriority(self::APPOINTMENT_PRIORITY_NOTIFIED);

            return $this->priority;
        }

        if ($this->isInitial()) {
            $this->priority = DomainContext::getAppointmentPriority(self::APPOINTMENT_PRIORITY_INITIAL);

            return $this->priority;
        }

        if ($this->isReservice()) {
            $this->priority = DomainContext::getAppointmentPriority(self::APPOINTMENT_PRIORITY_RESERVICE);

            return $this->priority;
        }

        if ($this->isHalfDay()) {
            $this->priority = DomainContext::getAppointmentPriority(self::APPOINTMENT_PRIORITY_HALF_DAY);

            return $this->priority;
        }

        $this->priority = DomainContext::getAppointmentPriority(self::APPOINTMENT_PRIORITY_DEFAULT);

        return $this->priority;
    }

    /**
     * @return bool
     */
    public function isInitial(): bool
    {
        return str_contains(Str::lower($this->getDescription()), Str::lower(self::APPOINTMENT_PRIORITY_INITIAL));
    }

    /**
     * @return int
     */
    public function getWeight(): int
    {
        return $this->isInitial() ? self::WEIGHTED_INITIAL : self::WEIGHTED_REGULAR;
    }

    /**
     * @return bool
     */
    public function isReservice(): bool
    {
        return str_contains(Str::lower($this->getDescription()), Str::lower(self::APPOINTMENT_PRIORITY_RESERVICE));
    }

    /**
     * @return bool
     */
    public function isHalfDay(): bool
    {
        if (!$this->expectedArrival) {
            return false;
        }

        $midDay = Carbon::instance($this->expectedArrival->getStartAt())->midDay();

        return ($this->expectedArrival->getStartAt() < $midDay && $this->expectedArrival->getEndAt() <= $midDay) //AM
            || ($this->expectedArrival->getStartAt() >= $midDay && $this->expectedArrival->getEndAt() > $midDay); //PM
    }

    /**
     * @return bool
     */
    public function isNotified(): bool
    {
        return $this->notified;
    }

    /**
     * @param int $priority
     */
    public function setPriority(int $priority): Appointment
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPreferredTechId(): int|null
    {
        return $this->preferredTechId;
    }

    /**
     * @return Duration
     */
    public function getSetupDuration(): Duration
    {
        if (isset($this->setupDuration)) {
            return $this->setupDuration;
        }

        if ($this->isInitial()) {
            $this->setupDuration = Duration::fromMinutes(
                DomainContext::getAppointmentSetUpTime(self::APPOINTMENT_SETUP_INITIAL)
            );

            return $this->setupDuration;
        }

        $this->setupDuration = Duration::fromMinutes(
            DomainContext::getAppointmentSetUpTime(self::APPOINTMENT_SETUP_DEFAULT)
        );

        return $this->setupDuration;
    }

    /**
     * @param Duration $setupDuration
     *
     * @return Appointment
     */
    public function setSetupDuration(Duration $setupDuration): Appointment
    {
        $this->setupDuration = $setupDuration;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    /**
     * @return WorkEventType
     */
    public function getType(): WorkEventType
    {
        return WorkEventType::APPOINTMENT;
    }

    /**
     * @return Duration
     */
    public function getDuration(): Duration
    {
        if (isset($this->duration)) {
            return $this->duration;
        }

        $this->duration = Duration::fromMinutes(DomainContext::getAppointmentDefaultDuration());

        return $this->duration;
    }

    /**
     * @return Duration|null
     */
    public function getMinimumDuration(): Duration|null
    {
        return $this->serviceDuration?->getMinimumDuration();
    }

    /**
     * @return Duration|null
     */
    public function getMaximumDuration(): Duration|null
    {
        return $this->serviceDuration?->getMaximumDuration();
    }

    /**
     * @param PropertyDetails $propertyDetails
     * @param float|null $historicalAverageDuration
     * @param WeatherInfo|null $weatherInfo
     *
     * @return void
     */
    public function resolveServiceDuration(
        PropertyDetails $propertyDetails,
        float|null $historicalAverageDuration,
        WeatherInfo|null $weatherInfo,
    ): void {
        $this->serviceDuration = new ServiceDuration($propertyDetails, $historicalAverageDuration, $weatherInfo);
        $this->setDuration($this->serviceDuration->getOptimumDuration());
    }

    /**
     * @return int
     */
    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    /**
     * @return int
     */
    public function getOfficeId(): int
    {
        return $this->officeId;
    }

    /**
     * @param CarbonInterface $startAt
     *
     * @return static
     */
    public function setStartAtAndAdjustEndAt(CarbonInterface $startAt): static
    {
        $endAt = $startAt->clone()
            ->add($this->getSetupDuration()->getInterval())
            ->add($this->getDuration()->getInterval());

        $this->setTimeWindow(new TimeWindow($startAt, $endAt));

        return $this;
    }

    /**
     * @return Duration
     */
    public function getTotalServiceTime(): Duration
    {
        return $this->getDuration()->increase($this->getSetupDuration());
    }

    /**
     * @return $this
     */
    public function lock(TimeWindow $timeWindow, ServicePro $servicePro): self
    {
        $this->isLocked = true;
        $this->setExpectedArrival($timeWindow);
        $this->skills->add($servicePro->getPersonalSkill());
        $this->setPriority(DomainContext::getAppointmentPriority(self::APPOINTMENT_PRIORITY_LOCKED));

        return $this;
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->isLocked;
    }

    public function addSkillFromPreferredTech(): self
    {
        $this->skills->add(Skill::createPersonalSkillFromId($this->preferredTechId));

        return $this;
    }
}
