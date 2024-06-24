<div class="accordion" id="schedulingRun{{ $stateIndex }}">
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapse-{{ $stateId }}"
                    aria-expanded="{{ $stateIndex == 0 ? 'true' : 'false' }}"
                    aria-controls="collapse-{{ $stateId }}">
                <span class="me-2">State #{{ $stateId }}</span>
                <span class="badge bg-secondary me-2">Executed at: {{ $startAt }}</span>
                <span class="badge bg-success me-2">Routes: {{ $stats['routes_count'] }}</span>
                <span class="badge bg-primary me-2">Scheduled: {{ $stats['scheduled_services_count'] }}</span>
                <span class="badge bg-primary me-2">Re-scheduled: {{ $stats['rescheduled_services_count'] ?? 0 }}</span>
                <span class="badge bg-warning text-dark me-2">In pool: {{ $stats['pending_services_count'] }}</span>
                <span class="badge bg-warning text-dark me-2">On reschedule route: {{ $stats['pending_rescheduled_services'] ?? 0 }}</span>
            </button>
        </h2>
        <div id="collapse-{{ $stateId }}" class="accordion-collapse collapse" data-bs-parent="#schedulingRun{{ $stateIndex }}">
            <div class="accordion-body">
                <div class="row">
                    <div class="col-3">
                        @if(isset($stats))
                            <table class="table table-sm">
                                <thead>
                                <tr>
                                    <th scope="col">Title</th>
                                    <th scope="col">Value</th>
                                </tr>
                                </thead>
                                <tbody>
                                    @foreach($stats as $key => $value)
                                        <tr>
                                            <td>{{ __("messages.automated_scheduling.stats.$key") }}</td>
                                            <td>{{ $value }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-auto mt-2"><a href="{{ route('scheduling-map', ['state_id' => $stateId]) }}" target="_blank"><i class="bi bi-map-fill"></i> View Map</a></div>
                </div>
                <div class="row">
                    <div class="col-auto mt-2"><a href="{{ route('scheduling-model', ['state_id' => $stateId]) }}" target="_blank"><i class="bi bi-controller"></i> Open in Playground</a></div>
                </div>
                <div class="row">
                    <div class="col-auto mt-2"><a href="{{ route('scheduling-export', ['state_id' => $stateId]) }}" target="_blank"><i class="bi bi-floppy"></i> Export</a></div>
                </div>
            </div>
        </div>
    </div>
</div>
