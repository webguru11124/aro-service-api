@extends('layouts.master-template')

@section('styles')
    @parent
    <style>
        #executions-table thead th {
            position: sticky;
            top: 0;
            background-color: white;
        }
    </style>
@endsection

@section('content')
    <x-overview-navigation :process-date="$selectedDate"></x-overview-navigation>
    <form>
        <div class="row">
            <div class="col-auto">
                <a href="{{ route('scheduling-executions', ['execution_date' => $prevDate]) }}" class="btn btn-primary" title="Previous day">&lt;</a>
                <a href="{{ route('scheduling-executions', ['execution_date' => $nextDate]) }}" class="btn btn-primary" title="Next day">&gt;</a>
            </div>
            <div class="col-auto">
                <input type="text" class="form-control" id="inputDate" placeholder="Date in YYYY-MM-DD format"
                       name="execution_date" value="{{ $selectedDate }}" title="Scheduling Executed At">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
            <div class="col text-end">
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#legend_modal">Legend</button>
            </div>
        </div>
    </form>
    <hr>
    <table class="table" id="executions-table">
        <thead>
            <th>Office</th>
            @foreach($dates as $date)
                <th>{{ $date }}</th>
            @endforeach
        </thead>
        <tbody>
            @foreach($offices as $officeId => $office)
            <tr>
                <td>{{ $office }} (#{{ $officeId }})</td>
                @foreach($dates as $date)
                    <td>
                        @if($executions->get($officeId)?->get($date)?->isNotEmpty())
                            @foreach($executions->get($officeId)->get($date) as $execution)
                                <x-scheduling-execution :execution="$execution" :link="route('scheduling-overview', ['scheduling_date' => $date, 'office_id' => $officeId, 'execution_date' => $selectedDate])"></x-scheduling-execution>
                            @endforeach
                        @endif
                    </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="modal fade" id="legend_modal" tabindex="-1" aria-labelledby="legendModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="legendModalLabel">Legend</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul>
                        <li><span class="badge bg-success">12:14:23</span> - Scheduling Execution Time</li>
                        <li>Timestamps are displayed in MST timezone</li>
                        <li><span class="badge bg-secondary">3: 43/2</span> - Total routes: Scheduled Services / Pending Services</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection
