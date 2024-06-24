@php
use App\Domain\RouteOptimization\Enums\WorkEventType;
@endphp
<div class="timeline-box d-flex" style="width: {{ $width }}px">
    @foreach($route['schedule'] as $event)
        @if($event['work_event_type'] === WorkEventType::START_LOCATION->value)
            <x-timeline-event-start :event="$event"></x-timeline-event-start>
        @endif
        @if($event['work_event_type'] === WorkEventType::END_LOCATION->value)
            <x-timeline-event-start :event="$event"></x-timeline-event-start>
        @endif
        @if($event['work_event_type'] === WorkEventType::APPOINTMENT->value)
            <x-timeline-event-appointment :event="$event"></x-timeline-event-appointment>
        @endif
        @if($event['work_event_type'] === WorkEventType::TRAVEL->value)
            <x-timeline-event-travel :event="$event"></x-timeline-event-travel>
        @endif
        @if($event['work_event_type'] === WorkEventType::BREAK->value)
            <x-timeline-event-break :event="$event"></x-timeline-event-break>
        @endif
        @if($event['work_event_type'] === WorkEventType::LUNCH->value)
            <x-timeline-event-break :event="$event"></x-timeline-event-break>
        @endif
        @if($event['work_event_type'] === WorkEventType::WAITING->value)
            <x-timeline-event-waiting :event="$event"></x-timeline-event-waiting>
        @endif
        @if($event['work_event_type'] === WorkEventType::MEETING->value)
            <x-timeline-event-meeting :event="$event"></x-timeline-event-meeting>
        @endif
        @if($event['work_event_type'] === WorkEventType::RESERVED_TIME->value)
            <x-timeline-event-reserved :event="$event"></x-timeline-event-reserved>
        @endif
    @endforeach
</div>
