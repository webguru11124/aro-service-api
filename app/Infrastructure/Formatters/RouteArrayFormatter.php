<?php

declare(strict_types=1);

namespace App\Infrastructure\Formatters;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\WorkEvent\AbstractWorkEvent;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Entities\WorkEvent\Meeting;
use App\Domain\RouteOptimization\Entities\WorkEvent\ReservedTime;
use App\Domain\RouteOptimization\Entities\WorkEvent\Travel;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkEvent;
use App\Domain\RouteOptimization\Enums\WorkEventType;
use App\Domain\RouteOptimization\Services\RouteStatisticsService;
use App\Domain\RouteOptimization\ValueObjects\LocationEvent;
use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Metric;
use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Domain\SharedKernel\ValueObjects\Coordinate;

class RouteArrayFormatter
{
    public function __construct(
        private RouteStatisticsService $routeStatisticsService,
    ) {
    }

    /**
     * Formats the Route as an array
     *
     * @param Route $route
     *
     * @return mixed[]
     */
    public function format(Route $route): array
    {
        return $this->formatRoutesArray($route);
    }

    /**
     * @return mixed[]
     */
    private function formatRoutesArray(Route $route): array
    {
        return [
            'id' => $route->getId(),
            'schedule' => $this->formatSchedule($route),
            'details' => $this->formatDetails($route),
            'service_pro' => $this->formatServicePro($route),
            'metrics' => $this->formatMetrics($route),
            'stats' => $this->routeStatisticsService->getStats($route)->toArray(),
        ];
    }

    /**
     * @return mixed[]
     */
    private function formatSchedule(Route $route): array
    {
        $formattedWorkEvents = [];

        /** @var LocationEvent|Appointment|Travel|Meeting|WorkBreak|ReservedTime $workEvent */
        foreach ($route->getWorkEvents() as $workEvent) {
            $formattedWorkEvents[] = match ($workEvent->getType()) {
                WorkEventType::START_LOCATION, WorkEventType::END_LOCATION => $this->formatStartEndLocation($workEvent),
                WorkEventType::APPOINTMENT => $this->formatAppointment($workEvent),
                WorkEventType::TRAVEL => $this->formatTravel($workEvent),
                WorkEventType::MEETING => $this->formatMeeting($workEvent),
                WorkEventType::BREAK, WorkEventType::LUNCH => $this->formatBreak($workEvent),
                WorkEventType::RESERVED_TIME => $this->formatReservedTime($workEvent),

                default => $this->formatEvent($workEvent),
            };
        }

        return $formattedWorkEvents;
    }

    /**
     * @param Travel $travel
     *
     * @return array<string, mixed>
     */
    private function formatTravel(Travel $travel): array
    {
        return array_merge(
            $this->formatEvent($travel),
            [
                'travel_miles' => $travel->getDistance()->getMiles(),
            ]
        );
    }

    /**
     * @param WorkBreak $workBreak
     *
     * @return array<string, mixed>
     */
    private function formatBreak(WorkBreak $workBreak): array
    {
        return array_merge(
            $this->formatEvent($workBreak),
            [
                'id' => $workBreak->getId(),
                'expected_time_window' => $this->formatExpectedTimeWindow($workBreak),
            ]
        );
    }

    /**
     * @param ReservedTime $reservedTime
     *
     * @return array<string, mixed>
     */
    private function formatReservedTime(ReservedTime $reservedTime): array
    {
        return array_merge(
            $this->formatEvent($reservedTime),
            [
                'id' => $reservedTime->getId(),
                'expected_time_window' => $this->formatExpectedTimeWindow($reservedTime),
            ]
        );
    }

    /**
     * @param Meeting $meeting
     *
     * @return array<string, mixed>
     */
    private function formatMeeting(Meeting $meeting): array
    {
        return array_merge(
            $this->formatEvent($meeting),
            [
                'location' => $this->formatLocation($meeting->getLocation()),
                'expected_time_window' => $this->formatExpectedTimeWindow($meeting),
            ]
        );
    }

    /**
     * @param LocationEvent $locationEvent
     *
     * @return array<string, mixed>
     */
    private function formatStartEndLocation(LocationEvent $locationEvent): array
    {
        return array_merge(
            $this->formatEvent($locationEvent),
            [
                'location' => $this->formatLocation($locationEvent->getLocation()),
            ]
        );
    }

    /**
     * @param Appointment $appointment
     *
     * @return array<string, mixed>
     */
    private function formatAppointment(Appointment $appointment): array
    {
        return array_merge(
            $this->formatEvent($appointment),
            [
                'appointment_id' => $appointment->getId(),
                'priority' => $appointment->getPriority(),
                'setup_duration' => $appointment->getSetupDuration()->getTotalMinutes(),
                'service_duration' => $appointment->getDuration()->getTotalMinutes(),
                'minimum_duration' => $appointment->getMinimumDuration()?->getTotalMinutes(),
                'maximum_duration' => $appointment->getMaximumDuration()?->getTotalMinutes(),
                'is_locked' => (int) $appointment->isLocked(),
                'preferred_tech_id' => $appointment->getPreferredTechId(),
                'is_notified' => (int) $appointment->isNotified(),
                'customer_id' => $appointment->getCustomerId(),
                'location' => $this->formatLocation($appointment->getLocation()),
                'expected_time_window' => $this->formatExpectedTimeWindow($appointment),
                'skills' => $appointment->getSkills()->map(fn (Skill $skill) => $skill->getLiteral())->values()->toArray(),
            ]
        );
    }

    /**
     * @param WorkEvent $workEvent
     *
     * @return array<string, mixed>
     */
    private function formatEvent(WorkEvent $workEvent): array
    {
        return [
            'work_event_type' => $workEvent->getType()->value,
            'scheduled_time_window' => [
                'start' => $workEvent->getTimeWindow()?->getStartAt()->toDatetimeString(),
                'end' => $workEvent->getTimeWindow()?->getEndAt()->toDatetimeString(),
            ],
            'description' => $workEvent->getDescription(),
        ];
    }

    /**
     * @param Coordinate $location
     *
     * @return array<string, float>
     */
    private function formatLocation(Coordinate $location): array
    {
        return [
            'lat' => $location->getLatitude(),
            'lon' => $location->getLongitude(),
        ];
    }

    /**
     * @param AbstractWorkEvent|LocationEvent $workEvent
     *
     * @return array<string, string>
     */
    private function formatExpectedTimeWindow(AbstractWorkEvent|LocationEvent $workEvent): array
    {
        return [
            'start' => $workEvent->getExpectedArrival()?->getStartAt()->toDatetimeString(),
            'end' => $workEvent->getExpectedArrival()?->getEndAt()->toDatetimeString(),
        ];
    }

    /**
     * @return mixed[]
     */
    private function formatDetails(Route $route): array
    {
        return [
            'route_type' => $route->getRouteType()->value,
            'start_at' => $route->getTimeWindow()->getStartAt()->toDateTimeString(),
            'end_at' => $route->getTimeWindow()->getEndAt()->toDateTimeString(),
            'actual_capacity' => $route->getActualCapacityCount(),
            'max_capacity' => $route->getMaxCapacity(),
            'start_location' => [
                'lon' => $route->getStartLocation()->getLocation()->getLongitude(),
                'lat' => $route->getStartLocation()->getLocation()->getLatitude(),
            ],
            'end_location' => [
                'lon' => $route->getEndLocation()->getLocation()->getLongitude(),
                'lat' => $route->getEndLocation()->getLocation()->getLatitude(),
            ],
            'optimization_score' => $route->getOptimizationScore()->value(),
        ];
    }

    /**
     * @return mixed[]
     */
    private function formatServicePro(Route $route): array
    {
        $servicePro = $route->getServicePro();

        return [
            'id' => $servicePro->getId(),
            'name' => $servicePro->getName(),
            'workday_id' => $servicePro->getWorkdayId(),
            'working_hours' => [
                'start_at' => $servicePro->getWorkingHours()->getStartAt()->toTimeString(),
                'end_at' => $servicePro->getWorkingHours()->getEndAt()->toTimeString(),
            ],
            'skills' => $servicePro->getSkillsWithoutPersonal()->map(fn (Skill $skill) => $skill->getLiteral())->values()->toArray(),
            'start_location' => [
                'lat' => $servicePro->getStartLocation()->getLatitude(),
                'lon' => $servicePro->getStartLocation()->getLongitude(),
            ],
            'end_location' => [
                'lat' => $servicePro->getEndLocation()->getLatitude(),
                'lon' => $servicePro->getEndLocation()->getLongitude(),
            ],
        ];
    }

    /**
     * @return mixed[]
     */
    private function formatMetrics(Route $route): array
    {
        $routeMetrics = $route->getMetrics();

        return $routeMetrics->map(function (Metric $metric) {
            return [
                'name' => $metric->getKey()->value,
                'title' => $metric->getName(),
                'weight' => $metric->getWeight()->value(),
                'value' => $metric->getValue(),
                'score' => $metric->getScore()->value(),
            ];
        })->toArray();
    }
}
