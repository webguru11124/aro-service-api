@extends('layouts.master-template')

{{-- Get and format data needed for the page --}}
@php
    $routes = $state['routes']->toArray();
    $score = ($state['metrics']['optimization_score'] ?? 0) * 100;
@endphp

@section('content')
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-3">
    <div class="container-fluid">
        <div class="navbar-brand">Optimization</div>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
            <div class="navbar-nav">
                <a class="nav-link" href="{{ URL::previous() }}">Back to Previous</a>
            </div>
        </div>
    </div>
</nav>
<div class="container-fluid">
    <div class="row">
        <div class="col">
            <div class="d-flex justify-content-between">
                <h1>Optimization Map</h1>
                <h2>Office Optimization Score: <x-optimization-score :score="$score"></x-optimization-score></h2>
            </div>

            <p class="font-italic">Click on a map marker to see details for that route</p>
        </div>
    </div>
    <div class="row">
        <div class="col-2">
            <table class="table table-hover table-sm">
                <thead>
                <tr>
                    <th scope="col">Route #</th>
                    <th scope="col">Service Pro</th>
                    <th scope="col">Score</th>
                </tr>
                </thead>
                <tbody>
                @foreach($routes as $route)
                    @php $score = $route['details']['optimization_score'] * 100 @endphp
                    <tr onclick="showRouteDetails({{ $route['id'] }})" id="route-select-{{ $route['id'] }}">
                        <td><i style="color:lightgray" class="bi bi-square-fill route-color" title=""></i> {{ $route['id'] }}</td>
                        <td><span class="route-service-pro">{{ $route['service_pro']['name'] }}</span></td>
                        <td>
                            <x-optimization-score :score="$score"></x-optimization-score>
                            <span class="d-none route-score">{{ $score }}</span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="col">
            <x-routes-map :routes="$routes"></x-routes-map>
        </div>
    </div>
    <div class="row mt-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-italic">Timeline</h5>
                <div class="timeline-wrapper" id="routes-timeline">
                    @foreach($routes as $route)
                        <div>#{{ $route['id'] }} {{ $route['service_pro']['name'] }}</div>
                        <div id="route-timeline-{{ $route['id'] }}" class="route-timeline p-1">
                            <x-route-timeline :route="$route"></x-route-timeline>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="card">
            <div class="card-body">
                <div class="card-title d-flex justify-content-between">
                    <div>
                        <h5 class="mr-2 d-inline"><i id="route-color" style="color:lightgray" class="bi bi-square-fill" ></i></h5>
                        <h5 class="card-title d-inline text-italic" id="route-title">Route: (Select Map Marker to Display)</h5>
                    </div>
                    <h5 class="card-title">Route Optimization Score: <x-optimization-score elementId="optimization-score" score="0" hidden="true"></x-optimization-score></h5> {{-- Temnp set score to zero until we unhide it --}}
                </div>
                <h6 class="card-subtitle mb-3" id="service-pro-title">Service Pro: (Select Map Marker to Display)</h6>
                <div>
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="metrics-tab" data-bs-toggle="tab" data-bs-target="#metrics" type="button" role="tab" aria-controls="metrics" aria-selected="true">Metrics</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button" role="tab" aria-controls="stats" aria-selected="true">Stats</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="schedule-tab" data-bs-toggle="tab" data-bs-target="#schedule" type="button" role="tab" aria-controls="schedule" aria-selected="false">Schedule</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="metrics" role="tabpanel" aria-labelledby="metrics-tab">
                            @foreach($routes as $route)
                            @php
                                if (!empty($route['metric_details'])) {
                                    usort($route['metric_details'], fn ($metric1, $metric2) => $metric2['weight'] <=> $metric1['weight']);
                                }
                            @endphp
                            <div id="route-metrics-{{ $route['id']}}" class="d-none route-metrics">
                                <x-metric-details :metric-details="$route['metrics']"></x-metric-details>
                            </div>
                            @endforeach
                        </div>
                        <div class="tab-pane fade" id="stats" role="tabpanel" aria-labelledby="stats-tab">
                            @foreach($routes as $route)
                                <div id="route-stats-{{ $route['id']}}" class="d-none route-stats">
                                    <x-stats-details :stats-details="$route['stats']"></x-stats-details>
                                </div>
                            @endforeach
                        </div>
                        <div class="tab-pane fade" id="schedule" role="tabpanel" aria-labelledby="schedule-tab">
                            @foreach($routes as $route)
                                <div id="route-{{ $route['id'] }}" class="d-none route-details row">
                                    Will be there soon
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div

@endSection

@section('styles')
    <style>
        .tab-pane {
            margin: 2%
        }
    </style>
@endSection

@section('scripts-body')
    <script>

        async function showRouteDetails(routeId) {
            let routeSelectEl = document.getElementById('route-select-' + routeId);
            let pinColor = routeSelectEl.querySelector('.route-color').style.color;
            let serviceProName = routeSelectEl.querySelector('.route-service-pro').innerHTML;
            let routeOptimizationScore = routeSelectEl.querySelector('.route-score').innerHTML;

            // Add hide the previous route details/metrics/stats
            document.querySelectorAll('.route-details').forEach((routeDetails) => {
                routeDetails.classList.add('d-none');
            });
            document.querySelectorAll('.route-metrics').forEach((routeMetrics) => {
                routeMetrics.classList.add('d-none');
            });
            document.querySelectorAll('.route-stats').forEach((routeStats) => {
                routeStats.classList.add('d-none');
            });

            // Set the route and service pro title on the page
            document.getElementById('route-title').innerHTML = "Route: " + routeId;
            document.getElementById('service-pro-title').innerHTML = "Service Pro: " + serviceProName;

            // Display the route details/metrics for the specific route
            document.getElementById(`route-${routeId}`).classList.remove('d-none');
            document.getElementById(`route-metrics-${routeId}`).classList.remove('d-none');
            document.getElementById(`route-stats-${routeId}`).classList.remove('d-none');
            document.getElementById('optimization-score').classList.remove('d-none');

            // Set the optimization score
            document.getElementById('optimization-score').innerHTML = routeOptimizationScore + "%";

            // Set the color for the route
            document.getElementById('route-color').style.color = pinColor;

            // Determine color of score
            if(routeOptimizationScore >= 90) {
                document.getElementById('optimization-score').classList.add('bg-success');
                document.getElementById('optimization-score').classList.remove('bg-warning');
                document.getElementById('optimization-score').classList.remove('bg-danger');
            } else if(routeOptimizationScore >= 70) {
                document.getElementById('optimization-score').classList.remove('bg-success');
                document.getElementById('optimization-score').classList.add('bg-warning');
                document.getElementById('optimization-score').classList.remove('bg-danger');
            } else {
                document.getElementById('optimization-score').classList.remove('bg-success');
                document.getElementById('optimization-score').classList.remove('bg-warning');
                document.getElementById('optimization-score').classList.add('bg-danger');
            }

            // Also display the rules list
            let lists = document.getElementsByClassName(`rules-list`);
            for(let list of lists) {
                list.classList.remove('d-none');
            }
        }

    </script>
@endSection
