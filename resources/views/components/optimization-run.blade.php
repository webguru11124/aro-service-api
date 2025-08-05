<div class="accordion" id="optimizationRun{{ $stateIndex }}">
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapse-{{ $stateId }}"
                    aria-expanded="{{ $stateIndex == 0 ? 'true' : 'false' }}"
                    aria-controls="collapse-{{ $stateId }}">
                <span class="me-2">State #{{ $stateId }}</span>
                <span class="badge bg-secondary me-2">Executed at: {{ $startAt }}</span>
                <span class="badge bg-secondary me-2">Engine: {{ $engine }}</span>
                @if(!empty($processing))
                    <span class="badge bg-secondary me-2">Processing: {{ $processing }} (sec)</span>
                    <span class="badge bg-secondary me-2">Routes: {{ $routesCount }}</span>
                    <span class="badge bg-secondary me-2">Appointments: {{ $appointmentsCount }}</span>
                    <span class="badge bg-warning text-dark me-2">Overbooked/Reschedule: {{ $overbookedCount }}</span>
                    <span class="badge bg-warning text-dark me-2">Unassigned: {{ $unassignedCount }}</span>
                    <x-optimization-score :score="$score" text="Avg. Score: "></x-optimization-score>
                    @if($isSimulation)<span class="badge bg-primary ms-2" title="No changes were made in PestRoutes">simulation</span>@endif
                @else
                    <span class="badge bg-warning text-dark me-2">Optimization failed</span>
                @endif
            </button>
        </h2>
        <div id="collapse-{{ $stateId }}" class="accordion-collapse collapse" data-bs-parent="#optimizationRun{{ $stateIndex }}">
            <div class="accordion-body">
                <div class="row">
                    <div class="col-4">
                        <table class="table table-sm">
                            <thead>
                            <tr>
                                <th scope="col">Route #</th>
                                <th scope="col">Service Pro</th>
                                <th scope="col">SP Skills</th>
                                <th scope="col">Score</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($routes as $route)
                                <x-scheduled-route :route="$route"></x-scheduled-route>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(!empty($weatherForecast))
                        <div class="col-3">
                            <x-weather-forecast :weatherForecast="$weatherForecast"></x-weather-forecast>
                        </div>
                    @endif
                    @if(!empty($stats))
                        <div class="col-3">
                            <x-stats-details :stats-details="$stats"></x-stats-details>
                        </div>
                    @endif
                </div>

                <div class="row">
                    <div class="col-auto mt-2">
                        <a href="#rules_modal_{{ $stateId }}" data-bs-toggle="modal" data-bs-target="#rules_modal_{{ $stateId }}"><i class="bi bi-gear"></i> View Applied Rules</a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-auto mt-2">
                        <a href="{{ route('optimization-simulation', ['state_id' => $stateId]) }}" target="_blank"><i class="bi bi-controller"></i> View Simulations</a>
                    </div>
                </div>
                <div class="row">
                    <div class="col-auto mt-2">
                        <a href="{{ route('optimization-details', ['state_id' => $stateId]) }}" target="_blank"><i class="bi bi-card-list"></i> View Details</a>
                    </div>
                </div>

                @if(!empty($resultStateId)) {{-- Display map if there is result state --}}
                <div class="row">
                    <div class="col-auto mt-2">
                        <a href="{{ route('optimization-map', ['state_id' => $resultStateId]) }}" target="_blank"><i class="bi bi-map-fill"></i> View Map</a>
                    </div>
                </div>
                @endif

                <div class="row">
                    <div class="col-auto mt-2">
                        <a href="{{ $datadogLink }}" target="_blank"><i class="bi bi-gear-fill"></i> View Logs in Datadog</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<x-applied-rules-modal :id="$stateId" :rules="$appliedRules"></x-applied-rules-modal>
