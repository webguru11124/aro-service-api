@extends('layouts.master-template')

@section('content')
    <x-overview-navigation :execution-date="$executionDate" :process-date="$optimizationDate" :office-id="$selectedOfficeId"></x-overview-navigation>
    <form>
        <div class="row">
            <div class="col-auto">
                <a href="{{ route('optimization-overview', ['optimization_date' => $prevDate, 'office_id' => $selectedOfficeId]) }}" class="btn btn-primary" title="Previous day">&lt;</a>
                <a href="{{ route('optimization-overview', ['optimization_date' => $nextDate, 'office_id' => $selectedOfficeId]) }}" class="btn btn-primary" title="Next day">&gt;</a>
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
    <ul class="nav nav-tabs" id="stateTabs" role="tablist">
        @foreach($createdAtDates as $dateIndex => $date)
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $date == $executionDate ? 'active' : '' }}" aria-current="page"
                        href="{{ route('optimization-overview', ['optimization_date' => $optimizationDate, 'execution_date' => $date, 'office_id' => $selectedOfficeId]) }}"
                    >{{ $date }}</a>
            </li>
        @endforeach
    </ul>
    <div class="tab-content" id="stateTabContent">
        @foreach($createdAtDates as $dateIndex => $date)
            <div class="p-3 tab-pane fade border border-top-0 {{ $date == $executionDate ? 'show active' : '' }}"
                 id="tab-pane-{{ $dateIndex }}" role="tabpanel" tabindex="0">
                @foreach($states[$date] as $stateIndex => $state)
                    <x-optimization-run :state-index="$stateIndex" :state="$state"></x-optimization-run>
                @endforeach
            </div>
        @endforeach
    </div>
@endsection
