@extends('layouts.master-template')
@php
    $preState = $states['pre_state'];
    $stateId = $preState['id'];
    $routes = $preState['routes'];
    $postState = $states['post_state'] ?? [];
    $simStates = $states['sim_states'] ?? [];
    $date = Carbon\Carbon::parse($preState['state']['optimization_window_start']);
    $rules = collect($postState['rules'] ?? [])
        ->unique('id')
        ->toArray();
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
                    <a class="nav-link" href="{{ URL::previous() }}">Back to Overview</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <div class="col">
                <div class="d-flex justify-content-between">
                    <h1>Optimization State <span class="text-secondary">#{{ $stateId }}</span></h1>
                    <h2>
                        Date: {{ $date->toDateString() }}
                    </h2>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <table class="table table-sm">
                    <thead>
                    <tr>
                        <th scope="col">Route #</th>
                        <th scope="col">Service Pro</th>
                        @if(!empty($postState))
                            <th scope="col">Post #{{ $postState['id'] }}</th>
                        @endif
                        @foreach($simStates as $state)
                            <th scope="col">Sim #{{ $state['id'] }}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($routes as $route)
                        @php
                            $postRoute = $postState['routes'][$loop->index] ?? [];
                            $postScore = ($postRoute['details']['optimization_score'] ?? 0) * 100;
                        @endphp
                        <tr>
                            <td>{{ $route['id'] }}</td>
                            <td>{{ $route['service_pro']['name'] }} (#{{ $route['service_pro']['id'] }})</td>
                            @if(!empty($postState))
                                <td><x-optimization-score :score="$postScore" text="Score: "></x-optimization-score></td>
                            @endif
                            @foreach($simStates as $simState)
                                @php
                                    $simScore = $simState['routes'][$loop->parent->index]['details']['optimization_score'] * 100;
                                @endphp
                                <td><x-optimization-score :score="$simScore" text="Score: "></x-optimization-score></td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="2"></td>
                        @if(!empty($postState))
                            @php $postScore = ($postState['metrics']['optimization_score'] ?? 0) * 100; @endphp
                            <td><x-optimization-score :score="$postScore" text="Avg. Score: "></x-optimization-score></td>
                        @endif
                        @foreach($simStates as $state)
                            @php $simScore = ($state['metrics']['optimization_score'] ?? 0) * 100; @endphp
                            <td><x-optimization-score :score="$simScore" text="Avg. Score: "></x-optimization-score></td>
                        @endforeach
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        @if(!empty($postState))
                            <td>
                                <div><a href="{{ route('optimization-map', ['state_id' => $postState['id']]) }}" target="_blank"><i class="bi bi-map-fill"></i> View Map</a></div>
                                <div><a href="#rules_modal_{{ $postState['id'] }}" data-bs-toggle="modal" data-bs-target="#rules_modal_{{ $postState['id'] }}"><i class="bi bi-gear"></i> View Applied Rules</a></div>
                                <div><a href="{{ route('optimization-details', ['state_id' => $stateId,]) }}" target="_blank"><i class="bi bi-card-list"></i> View Details</a></div>
                            </td>
                        @endif
                        @foreach($simStates as $simState)
                            <td>
                                <div><a href="{{ route('optimization-map', ['state_id' => $simState['id']]) }}" target="_blank"><i class="bi bi-map-fill"></i> View Map</a></div>
                                <div><a href="#rules_modal_{{ $simState['id'] }}" data-bs-toggle="modal" data-bs-target="#rules_modal_{{ $simState['id'] }}"><i class="bi bi-gear"></i> View Applied Rules</a></div>
                                <div><a href="{{ route('optimization-details', ['state_id' => $stateId, 'sim_state_id' => $simState['id']]) }}" target="_blank"><i class="bi bi-card-list"></i> View Details</a></div>
                            </td>
                        @endforeach
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if(session('results'))
            <div class="row">
                <div class="col-auto">
                    <div class="alert alert-info">
                        {{ session('results') ?? '' }}
                    </div>
                </div>
            </div>
        @endif

        <div class="row">
            <div class="col-auto mt-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#run_simulation_modal_{{ $stateId }}">
                    Simulate Optimization Run
                </button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="run_simulation_modal_{{ $stateId }}" tabindex="-1" aria-labelledby="rulesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="legendModalLabel">Run Simulation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Select business rule that should be applied during optimization simulation:</p>
                    <form action="{{ route('optimization-simulation.run') }}" method="POST">
                        @csrf
                        <input type="hidden" name="state_id" value="{{ $stateId }}">
                        <div class="form-group">
                            @foreach($rules as $rule)
                                <div class="form-check form-switch">
                                    <input type="hidden" name="rule_name[{{ $loop->index }}]" value="{{ $rule['id'] }}">
                                    <input class="form-check-input" type="checkbox" id="{{ $rule['id'] }}" name="rule_trigger[{{ $rule['id'] }}]" {{ $rule['is_triggered'] ? 'checked' : '' }}>
                                    <label class="form-check-label" for="flexSwitchCheckDefault">{{ $rule['name'] }} <i role="button" data-bs-toggle="collapse" data-bs-target="#RH-{{ $rule['id'] }}" class="bi bi-info-circle-fill text-info pe-auto"></i></label>
                                    <div class="collapse" id="RH-{{ $rule['id'] }}">
                                        <div class="alert alert-info">{{ $rule['description'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <hr>
                        <button type="submit" class="btn btn-primary">Run</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($postState)
        <x-applied-rules-modal :id="$postState['id']" :rules="$postState['rules']"></x-applied-rules-modal>
    @endif
    @foreach($simStates as $simState)
        <x-applied-rules-modal :id="$simState['id']" :rules="$simState['rules']"></x-applied-rules-modal>
    @endforeach
@endsection
