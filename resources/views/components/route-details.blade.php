<div class="row">
    <div class="col">
        <div class="row mt-1">
            <div class="col-6">
                <span class="badge bg-primary">{{ $routeTitle }}</span>
                <span class="badge bg-secondary" title="Spots / Max / Used">Capacity: {{ $actualCapacity }}/{{ $maxCapacity }}/{{ $capacity }}</span>
                <span class="badge bg-success">Appt: {{ $appointmentsCount }}</span>
            </div>
            <div class="col text-end">{{ $routeStartTime }} - {{ $routeEndTime }}</div>
        </div>
        <table class="table table-sm">
            <thead>
            <tr class="">
                <th scope="col">Time</th>
                <th scope="col">Event</th>
                <th scope="col">Duration</th>
                <th scope="col">Miles</th>
            </tr>
            </thead>
            <tbody>
            @foreach($schedule as $event)
                <x-work-event-details :work-event="$event"></x-work-event-details>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
