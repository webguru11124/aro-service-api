@extends('layouts.master-template')

@php
    $preState = $state['pre_state'];
    $planState = !empty($state['plan_state']) ? $state['plan_state'] : null;
    $postState = !empty($state['post_state']) ? $state['post_state'] : null;
    $simStates = !empty($state['sim_states']) ? $state['sim_states'] : [];
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
                <h1>Optimization Details <span class="text-secondary">#{{ $preState['id'] }}</span></h1>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <ul class="list-group">
                @foreach($preState['routes'] as $preRoute)
                    @php
                        $planRoute = $planState['routes'][$loop->index] ?? [];
                        $postRoute = $postState['routes'][$loop->index] ?? [];

                        $simRoutes = [];
                        foreach ($simStates as $simState) {
                            if (empty($simStateId) || $simState['id'] !== $simStateId) {
                                continue;
                            }
                            $simRoutes[] = $simState['routes'][$loop->index] ?? [];
                        }
                    @endphp
                    <x-scheduled-route-details :pre-route="$preRoute" :plan-route="$planRoute" :post-route="$postRoute" :sim-routes="$simRoutes"></x-scheduled-route-details>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
