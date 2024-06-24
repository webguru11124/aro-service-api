@extends('layouts.master-template')
@php
/** @var App\Domain\RouteOptimization\Entities\OptimizationState|null $state */
@endphp
@section('content')
    <x-overview-navigation :process-date="$optimizationDate" :office-id="$selectedOfficeId"></x-overview-navigation>

    <form>
        <div class="row">
            <div class="col-auto">
                <a href="{{ route('optimization-sandbox', ['optimization_date' => $prevDate, 'office_id' => $selectedOfficeId]) }}" class="btn btn-primary" title="Previous day">&lt;</a>
                <a href="{{ route('optimization-sandbox', ['optimization_date' => $nextDate, 'office_id' => $selectedOfficeId]) }}" class="btn btn-primary" title="Next day">&gt;</a>
            </div>
            <div class="col-auto">
                <input type="text" class="form-control" id="inputDate" placeholder="Date in YYYY-MM-DD format"
                       name="optimization_date" value="{{ $optimizationDate }}" title="Routes Scheduled On">
            </div>
            <div class="col-auto">
                <select class="form-select" aria-label="Default select example" id="inputOffice" name="office_id">
                    @foreach($offices as $officeId => $office)
                        <option value="{{ $officeId }}" {{ $officeId == $selectedOfficeId ? 'selected' : '' }}>{{ $office }} (#{{ $officeId }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </div>
    </form>
    <hr>
    <div class="container-fluid">
        @if(empty($state))
            <div class="row">
                <div class="col">
                    <h5>No routes found for the date and office</h5>
                    <p>{{ $error }}</p>
                </div>
            </div>
        @else
            <div class="row">
                <div class="col">
                    <div class="d-flex justify-content-between">
                        <h1>Routes Actual State <span class="text-secondary">#{{ $state->getId() }}</span></h1>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <div class="row">
                        <div class="col">
                            <div class="card">
                                <div class="card-body">
                                    <span class="badge bg-primary me-2">Created at: {{ $state->getCreatedAt()->toTimeString() }}</span>
                                    <span class="badge bg-secondary me-2">Routes: {{ $state->getRoutes()->count() }}</span>
                                </div>
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
                                    <th scope="col">Appointments</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($state->getRoutes() as $route)
                                    @php $collapseId = 'CS-' . $route->getId() @endphp
                                    <tr id="route-select-{{ $route->getId() }}">
                                        <td><a href="#{{ $collapseId }}" data-bs-toggle="collapse"><i style="color:lightgray" class="bi bi-square-fill route-color" title=""></i> {{ $route->getId() }}</a></td>
                                        <td>{{ $route->getServicePro()->getName() }} (#{{ $route->getServicePro()->getId() }})</td>
                                        <td>{{ $route->getAppointments()->count() }}</td>
                                    </tr>
                                    <tr class="collapse" id="{{ $collapseId }}">
                                        <td colspan="3">
                                            <table class="table table-bordered table-sm caption-top">
                                                <caption>Route events in detail</caption>
                                                <thead>
                                                <tr>
                                                    <th scope="col">Time</th>
                                                    <th scope="col">Event</th>
                                                    <th scope="col">Duration</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($route->getWorkEvents() as $event)
                                                    <x-work-event-object-details :work-event="$event"></x-work-event-object-details>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <x-routes-map :routes="$formattedRoutes"></x-routes-map>
                </div>
            </div>
        @endif
    </div>
@endsection
