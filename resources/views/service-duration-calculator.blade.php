@extends('layouts.master-template')
@section('styles')
    <style>
        .result-box {
            margin: 5px;
            min-height: 38px;
            display: inline-block;
            width: 100px;
            text-align: center;
            border-radius: 20px;
            border: 1px solid #dee2e6;
            padding: 5px;
        }
    </style>
@endsection
@section('content')
    <x-overview-navigation></x-overview-navigation>
    <h1 class="mb-4">Service Duration Calculator</h1>
    @php
        $activeTab = !empty(session('results')['linearFeetPerSecond']) ? 2 : 1;
    @endphp
    <div class="row">
        <div class="col-3">
            <ul class="nav nav-tabs" id="serviceDurationCalculatorTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab == 1 ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#serviceDurationForm" type="button" role="tab">Service Duration</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab == 2 ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#lfCalculationForm" type="button" role="tab">Calculate Lf</button>
                </li>
            </ul>

            <div class="tab-content" id="serviceDurationCalculatorTabContent">
                <!-- Service Duration Form -->
                <div class="tab-pane fade {{ $activeTab == 1 ? 'show active' : '' }} p-3" id="serviceDurationForm" role="tabpanel" tabindex="0">
                    <form action="{{ route('service-duration-calculations.calculate') }}" method="POST">
                        @csrf
                        <input type="hidden" name="calculateServiceDuration" value="1">

                        <div class="form-group mb-3">
                            <label for="linearFootPerSecond">Linear feet per second:</label>
                            <input type="text" class="form-control" id="linearFootPerSecond" name="linearFootPerSecond" placeholder="1.45" value="{{ old('linearFootPerSecond', '1.45') }}">
                            <small class="form-text text-muted">Default = 1.45</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="squareFootageOfHouse">Square footage of house:</label>
                            <input type="number" required class="form-control" id="squareFootageOfHouse" name="squareFootageOfHouse" placeholder="e.g. 2500" value="{{ old('squareFootageOfHouse') }}">
                        </div>

                        <div class="form-group mb-3">
                            <label for="squareFootageOfLot">Square footage of lot:</label>
                            <input type="number" required class="form-control" id="squareFootageOfLot" name="squareFootageOfLot" placeholder="e.g. 2500" value="{{ old('squareFootageOfLot') }}">
                        </div>

                        <button type="button" class="btn btn-secondary" onclick="customReset()">Reset</button>
                        <button type="submit" class="btn btn-primary">Calculate</button>

                        <!-- Results displayed here -->
                        @if(session('results'))
                            <div class="mt-3">
                                <div>Minimum Duration: <span class="result-box">{{ session('results')['minimumDuration'] ?? '' }}</span> minutes</div>
                                <div>Optimum Duration: <span class="result-box">{{ session('results')['optimumDuration'] ?? '' }}</span> minutes</div>
                                <div>Maximum Duration: <span class="result-box">{{ session('results')['maximumDuration'] ?? '' }}</span> minutes</div>
                            </div>
                        @else
                            <div class="mt-3">
                                <div>Minimum Duration: <span class="result-box">--</span></div>
                                <div>Optimum Duration: <span class="result-box">--</span></div>
                                <div>Maximum Duration: <span class="result-box">--</span></div>
                            </div>
                        @endif
                    </form>
                </div>
                <!-- Calculate Lf Form -->
                <div class="tab-pane fade p-3 {{ $activeTab == 2 ? 'show active' : '' }}" id="lfCalculationForm" role="tabpanel" tabindex="0">
                    <form action="{{ route('service-duration-calculations.calculate') }}" method="POST">
                        @csrf
                        <input type="hidden" name="calculateLf" value="1">

                        <div class="form-group mb-3">
                            <label for="actualDuration">Actual Duration (in minutes):</label>
                            <input type="float" required class="form-control" id="actualDuration" name="actualDuration" placeholder="e.g. 35.2" value="{{ old('actualDuration') }}">
                        </div>

                        <div class="form-group mb-3">
                            <label for="squareFootageOfHouseLf">Square footage of house:</label>
                            <input type="number" required class="form-control" id="squareFootageOfHouseLf" name="squareFootageOfHouse" placeholder="e.g. 2500" value="{{ old('squareFootageOfHouse') }}">
                        </div>

                        <div class="form-group mb-3">
                            <label for="squareFootageOfLotLf">Square footage of lot:</label>
                            <input type="number" required class="form-control" id="squareFootageOfLotLf" name="squareFootageOfLot" placeholder="e.g. 2500" value="{{ old('squareFootageOfLot') }}">
                        </div>

                        <button type="button" class="btn btn-secondary" onclick="customReset()">Reset</button>
                        <button type="submit" class="btn btn-primary">Calculate</button>

                        <!-- Results displayed here -->
                        @if(session('results'))
                            <div class="mt-3">
                                <div>Linear feet per second: <span class="result-box">≈ {{ session('results')['linearFeetPerSecond'] ?? '' }}</span> seconds</div>
                            </div>
                        @else
                            <div class="mt-3">
                                <div>Linear feet per second: <span class="result-box">--</span></div>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts-body')
    <script>
        function customReset() {
            document.getElementById('linearFootPerSecond').value = '1.45';
            document.getElementById('squareFootageOfHouse').value = '';
            document.getElementById('squareFootageOfLot').value = '';
            document.getElementById('squareFootageOfLotLf').value = '';
            document.getElementById('squareFootageOfHouseLf').value = '';
            document.getElementById('actualDuration').value = '';

            const resultBoxes = document.querySelectorAll('.result-box');
            resultBoxes.forEach(box => {
                box.innerHTML = '--';
            });
        }
    </script>
@endsection
