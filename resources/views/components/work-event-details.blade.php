<tr>
    <td class="text-center">
        <div>
            @if(!$isExtraWork)
                {{ $startAt }}@if(!$isLocation) {{ ' - ' . $endAt }} @endif
            @else
                <div class="text-muted">{{ $startAt }} - {{ $endAt }}</div>
            @endif
        </div>
        <div class="small-text text-muted" title="Expected time">{{ $expectedTime }}</div>
    </td>
    <td>
        @if($isLocked)
            <span title="Locked">🔒</span>
        @endif
        <span @if($location) title="Location: {{ $location }}" @endif >{{ $workEventIcon }}</span>
        <span>{{ $type }}</span>
        @if($appointmentId)
            <sup class="text-muted">#{{ $appointmentId }}</sup>
            <div class="small-text">{{ $description }}. Priority: {{ $priority }}, Service: {{ $serviceDuration }} + {{ $setupDuration }} mins</div>
            @if($minimumDuration)
                <div class="small-text">Predicted: min {{ $minimumDuration }}, max {{ $maximumDuration }} (mins)</div>
            @endif
            @if($preferredTechId)
                <div class="small-text">Preferred Tech Id: {{ $preferredTechId }}</div>
            @endif
        @endif
        @if($isMeeting)
            <div class="small-text">{{ $description }}</div>
        @endif
    </td>
    <td>
        @if(!$isExtraWork && !$isLocation)
            <span>{{ $duration }} Minutes</span>
        @endif
    </td>
    <td>
        @if($travelMiles)
            <span>{{ $travelMiles }}</span>
        @endif
    </td>
</tr>
