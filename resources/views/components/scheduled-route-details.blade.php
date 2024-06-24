<div class="accordion" id="optimizedRoute-{{ $routeId }}">
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $routeId }}" aria-controls="collapse-{{ $routeId }}">
                <span class="me-2">Route #{{ $routeId }}</span>
                @if(!empty($routeType))
                    <span class="badge bg-primary me-2">{{ $routeType }}</span>
                @endif
                <span class="badge bg-secondary me-2">{{ $employee }} (#{{ $employeeId }})</span>
                <span class="badge bg-secondary me-2">{{ $skills }}</span>
                <span class="badge bg-secondary me-2" title="Max working time sent optimization engine. Includes working time, breaks, driving time from/to home location">{{ $workingTime }}</span>
                <x-optimization-score :score="$score" text="Score: "></x-optimization-score>
            </button>
        </h2>
        <div id="collapse-{{ $routeId }}" class="accordion-collapse collapse" data-bs-parent="#optimizedRoute-{{ $routeId }}">
            <div class="accordion-body">
                <div class="row">
                    <div class="col-3 border-end">
                        <x-route-details route-title="PRE" :route="$preRoute"></x-route-details>
                    </div>
                    @if(!empty($planRoute))
                        <div class="col border-end">
                            <x-route-details route-title="PLAN" :route="$planRoute"></x-route-details>
                        </div>
                    @endif
                    @if(!empty($postRoute))
                        <div class="col-3 border-end">
                            <x-route-details route-title="POST" :route="$postRoute"></x-route-details>
                        </div>
                    @endif
                    @if(!empty($simRoutes))
                        @foreach($simRoutes as $simRoute)
                            <div class="col-3 border-end">
                                <x-route-details route-title="SIM" :route="$simRoute"></x-route-details>
                            </div>
                        @endforeach
                    @endif
                    @if(!empty($score))
                        <div class="col-3">
                            <div class="row">
                                <div class="col mt-2 text-center">
                                    <h5>Optimization Score</h5>
                                    <h4>{{ $score }}</h4>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col mb-2 text-center">
                                    <h6>Optimization Metrics</h6>
                                </div>
                            </div>
                            <x-metric-details :metric-details="$metrics"></x-metric-details>
                            @if(!empty($stats))
                                <div class="row">
                                    <div class="col mb-2 text-center">
                                        <h6>Optimization Stats</h6>
                                    </div>
                                </div>
                                <x-stats-details :stats-details="$stats"></x-stats-details>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
