<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Factories;

use App\Domain\RouteOptimization\DomainContext;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Entities\WorkEvent\Lunch;
use App\Domain\RouteOptimization\Entities\WorkEvent\Meeting;
use App\Domain\RouteOptimization\Entities\WorkEvent\ReservedTime;
use App\Domain\RouteOptimization\Entities\WorkEvent\Travel;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkEvent;
use App\Domain\RouteOptimization\Enums\WorkEventType;
use App\Domain\RouteOptimization\Exceptions\UnknownWorkEventTypeException;
use App\Domain\RouteOptimization\ValueObjects\EndLocation;
use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Domain\RouteOptimization\ValueObjects\StartLocation;
use App\Domain\RouteOptimization\ValueObjects\WorkEvent\ExtraWork;
use App\Domain\RouteOptimization\ValueObjects\WorkEvent\Waiting;
use App\Domain\SharedKernel\Entities\Office;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;

// TODO: Add unit tests for this class
class WorkEventFactory
{
    private Office $office;
    private Route $route;

    /**
     * @param array<string, mixed> $workEventData
     * @param Route $route
     * @param Office $office
     *
     * @return WorkEvent
     * @throws UnknownWorkEventTypeException
     */
    public function make(array $workEventData, Route $route, Office $office): WorkEvent
    {
        $this->office = $office;
        $this->route = $route;

        $workEventType = WorkEventType::tryFrom($workEventData['work_event_type']);

        return match ($workEventType) {
            WorkEventType::APPOINTMENT => $this->makeAppointment($workEventData),
            WorkEventType::MEETING => $this->makeMeeting($workEventData),
            WorkEventType::START_LOCATION => $this->makeStartLocation($workEventData),
            WorkEventType::END_LOCATION => $this->makeEndLocation($workEventData),
            WorkEventType::LUNCH => $this->makeLunch($workEventData),
            WorkEventType::BREAK => $this->makeBreak($workEventData),
            WorkEventType::TRAVEL => $this->makeTravel($workEventData),
            WorkEventType::EXTRA_WORK => $this->makeExtraWork($workEventData),
            WorkEventType::WAITING => $this->makeWaiting($workEventData),
            WorkEventType::RESERVED_TIME => $this->makeReservedTime($workEventData),
            default => throw UnknownWorkEventTypeException::instance($workEventData['work_event_type']),
        };
    }

    /**
     * @param array<string, mixed> $workEventData
     *
     * @return Appointment
     */
    private function makeAppointment(array $workEventData): Appointment
    {
        $skills = collect(array_map(fn ($skill) => Skill::tryFromState($skill), $workEventData['skills'] ?? []));
        $appointment = new Appointment(
            id: $workEventData['appointment_id'],
            description: $workEventData['description'],
            location: $this->getLocation($workEventData),
            notified: !empty($workEventData['is_notified']),
            officeId: $this->office->getId(),
            customerId: $workEventData['customer_id'] ?? 0,
            preferredTechId: $workEventData['preferred_tech_id'] ?? null,
            skills: $skills,
        );
        $appointment
            ->setTimeWindow($this->getTimeWindow($workEventData))
            ->setRouteId($this->route->getId())
            ->setSetupDuration(Duration::fromMinutes($workEventData['setup_duration']))
            ->setDuration(Duration::fromMinutes($workEventData['service_duration']))
            ->setExpectedArrival($this->getExpectedTimeWindow($workEventData));

        return $appointment;
    }

    /**
     * @param array<string, mixed> $workEventData
     *
     * @return Meeting
     */
    private function makeMeeting(array $workEventData): Meeting
    {
        $meeting = new Meeting(
            id: $this->route->getServicePro()->getPersonalSkill()->value,
            description: $workEventData['description'],
            timeWindow: $this->getTimeWindow($workEventData),
            location: $this->getLocation($workEventData),
        );

        $meeting->setExpectedArrival($this->getExpectedTimeWindow($workEventData));

        return $meeting;
    }

    /**
     * @param array<string, mixed> $workEventData
     *
     * @return StartLocation
     */
    private function makeStartLocation(array $workEventData): StartLocation
    {
        return new StartLocation(
            startAt: $this->getTimeWindow($workEventData)->getStartAt(),
            location: $this->getLocation($workEventData),
        );
    }

    /**
     * @param array<string, mixed> $workEventData
     *
     * @return EndLocation
     */
    private function makeEndLocation(array $workEventData): EndLocation
    {
        return new EndLocation(
            startAt: $this->getExpectedTimeWindow($workEventData)->getStartAt(),
            location: $this->getLocation($workEventData),
        );
    }

    /**
     * @param array<string, mixed> $workEventData
     *
     * @return Lunch
     */
    private function makeLunch(array $workEventData): Lunch
    {
        $lunch = new Lunch($workEventData['id'] ?? 0, $workEventData['description']);
        $lunch->setTimeWindow($this->getTimeWindow($workEventData));
        $lunch->setDuration(Duration::fromMinutes(DomainContext::getLunchDuration()));

        if (!empty($workEventData['expected_time_window'])) {
            $lunch->setExpectedArrival($this->getExpectedTimeWindow($workEventData));
        }

        return $lunch;
    }

    /**
     * @param array<string, mixed> $workEventData
     *
     * @return WorkBreak
     */
    private function makeBreak(array $workEventData): WorkBreak
    {
        $break = new WorkBreak($workEventData['id'] ?? 0, $workEventData['description']);
        $break->setTimeWindow($this->getTimeWindow($workEventData));
        $break->setDuration(Duration::fromMinutes(DomainContext::getWorkBreakDuration()));

        if (!empty($workEventData['expected_time_window'])) {
            $break->setExpectedArrival($this->getExpectedTimeWindow($workEventData));
        }

        return $break;
    }

    /**
     * @param array<string, mixed> $workEventData
     *
     * @return Travel
     */
    private function makeTravel(array $workEventData): Travel
    {
        return new Travel(
            Distance::fromMiles($workEventData['travel_miles']),
            $this->getTimeWindow($workEventData),
        );
    }

    /**
     * @param array<string, mixed> $workEventData
     *
     * @return ExtraWork
     */
    private function makeExtraWork(array $workEventData): ExtraWork
    {
        return new ExtraWork(
            timeWindow: $this->getTimeWindow($workEventData),
            startLocation: $this->route->getServicePro()->getStartLocation(), // that should be prev appointment location
            endLocation: $this->route->getServicePro()->getEndLocation(), // that should be next appointment location
            skills: $this->route->getServicePro()->getSkillsWithoutPersonal()
        );
    }

    /**
     * @param array<string, mixed> $workEventData
     *
     * @return Waiting
     */
    private function makeWaiting(array $workEventData): Waiting
    {
        return new Waiting(
            timeWindow: $this->getTimeWindow($workEventData),
        );
    }

    /**
     * @param array<string, mixed> $workEventData
     *
     * @return ReservedTime
     */
    private function makeReservedTime(array $workEventData): ReservedTime
    {
        $timeWindow = $this->getTimeWindow($workEventData);

        $reservedTime = new ReservedTime(
            id: $workEventData['id'] ?? 0,
            description: $workEventData['description'],
        );

        $reservedTime
            ->setTimeWindow($timeWindow)
            ->setExpectedArrival($this->getExpectedTimeWindow($workEventData))
            ->setDuration($timeWindow->getDuration());

        return $reservedTime;
    }

    /**
     * @param array<string, mixed> $workEventData
     *
     * @return TimeWindow
     */
    private function getTimeWindow(array $workEventData): TimeWindow
    {
        return new TimeWindow(
            Carbon::parse($workEventData['scheduled_time_window']['start'], $this->office->getTimezone()),
            Carbon::parse($workEventData['scheduled_time_window']['end'], $this->office->getTimezone()),
        );
    }

    /**
     * @param array<string, mixed> $workEventData
     *
     * @return TimeWindow
     */
    private function getExpectedTimeWindow(array $workEventData): TimeWindow
    {
        return new TimeWindow(
            Carbon::parse($workEventData['expected_time_window']['start'], $this->office->getTimezone()),
            Carbon::parse($workEventData['expected_time_window']['end'], $this->office->getTimezone()),
        );
    }

    /**
     * @param array<string, mixed> $workEventData
     *
     * @return Coordinate
     */
    private function getLocation(array $workEventData): Coordinate
    {
        return new Coordinate(
            $workEventData['location']['lat'],
            $workEventData['location']['lon'],
        );
    }
}
