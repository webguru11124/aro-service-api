@php
/**
 * @var App\Domain\RouteOptimization\Entities\WorkEvent\AbstractWorkEvent $workEvent
 */
$startAt = $workEvent->getTimeWindow()->getStartAt()->format('H:i');
$endAt = $workEvent->getTimeWindow()->getEndAt()->format('H:i');
@endphp
<tr>
    <td class="text-center">
        <div>{{ $startAt }} - {{ $endAt }}</div>
        <div class="small-text text-muted" title="Expected time">{{ $expectedTime }}</div>
    </td>
    <td>
        <span>{{ $workEventIcon }}</span>
        <span>{{ $workEvent->getType()->value }}</span>
        @if($workEvent->getType() === \App\Domain\RouteOptimization\Enums\WorkEventType::APPOINTMENT)
            <sup class="text-muted">#{{ $workEvent->getId() }}</sup>
            <div class="small-text">
                {{ $workEvent->getDescription() }}.
                Duration: {{ $workEvent->getDuration()->getTotalMinutes() }} + {{ $workEvent->getSetupDuration()->getTotalMinutes() }} mins</div>
        @endif
    </td>
    <td>
        <span>{{ $workEvent->getDuration()->getTotalMinutes() }} Minutes</span>
    </td>
</tr>
