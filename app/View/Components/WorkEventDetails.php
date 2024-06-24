<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Domain\RouteOptimization\Enums\WorkEventType;
use App\View\Shared\WorkEventIcons;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class WorkEventDetails extends Component
{
    public function __construct(
        private array $workEvent
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $startAt = Carbon::parse($this->workEvent['scheduled_time_window']['start']);
        $endAt = Carbon::parse($this->workEvent['scheduled_time_window']['end']);

        return view('components.work-event-details', [
            'type' => $this->workEvent['work_event_type'],
            'startAt' => $startAt->format('H:i'),
            'endAt' => $endAt->format('H:i'),
            'duration' => $endAt->diffInMinutes($startAt),
            'serviceDuration' => $this->workEvent['service_duration'] ?? null,
            'setupDuration' => $this->workEvent['setup_duration'] ?? null,
            'minimumDuration' => $this->workEvent['minimum_duration'] ?? null,
            'maximumDuration' => $this->workEvent['maximum_duration'] ?? null,
            'appointmentId' => $this->workEvent['appointment_id'] ?? null,
            'priority' => $this->workEvent['priority'] ?? null,
            'expectedTime' => $this->getExpectedTime(),
            'travelMiles' => $this->workEvent['travel_miles'] ?? null,
            'isExtraWork' => $this->workEvent['work_event_type'] == WorkEventType::EXTRA_WORK->value,
            'isLocation' => $this->workEvent['work_event_type'] == WorkEventType::START_LOCATION->value
                || $this->workEvent['work_event_type'] == WorkEventType::END_LOCATION->value,
            'isLocked' => !empty($this->workEvent['is_locked']),
            'preferredTechId' => !empty($this->workEvent['preferred_tech_id']),
            'workEventIcon' => WorkEventIcons::WORK_EVENT_ICONS[$this->workEvent['work_event_type']] ?? '',
            'isMeeting' => $this->workEvent['work_event_type'] == WorkEventType::MEETING->value,
            'description' => $this->workEvent['description'] ?? null,
            'location' => !empty($this->workEvent['location']) ? $this->workEvent['location']['lat'] . ',' . $this->workEvent['location']['lon'] : null,
        ]);
    }

    private function getExpectedTime(): string
    {
        $expectedTime = '';

        if (isset($this->workEvent['expected_time_window']['start'])) {
            $expectedTime = Carbon::parse($this->workEvent['expected_time_window']['start'])->format('H:i')
                . ' - ' . Carbon::parse($this->workEvent['expected_time_window']['end'])->format('H:i');
        }

        return $expectedTime;
    }
}
