<table class="table table-sm">
    <thead>
    <tr>
        <th scope="col">Metric</th>
        <th scope="col">Weight</th>
        <th scope="col">Value</th>
        <th class="text-end" scope="col">Score</th>
        <th class="text-end" scope="col">Rating</th>
    </tr>
    </thead>
    <tbody>
    @foreach($metricDetails as $metrics)
        <tr>
            <td>
                {{ $metrics['title'] }}
                @if(isset($metrics['name']) && in_array($metrics['name'], ['average_time_between_services', 'total_drive_time'])) in Minutes @endif
            </td>
            <td>{{ $metrics['weight'] * 100 }}%</td>
            <td>{{ round($metrics['value'], 2) }}</td>
            <td class="text-end">
                <b @style([
                    'color: red' => $metrics['score'] < 3,
                    'color: orange' => $metrics['score'] >= 3 && $metrics['score'] < 4 ,
                    'color: green' => $metrics['score'] >= 4,
                ])>{{ $metrics['score'] }}</b>
            </td>
            <td class="text-end">
                @for ($i = 1; $i < 6; $i++)
                    @php
                        $isChecked = $metrics['score'] >= $i;
                    @endphp
                    <span @class(['fa', 'fa-star', 'checked' => $isChecked ])></span>
                @endfor
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

@section('styles')
    @parent
    <style>
        .checked {
            color: orange
        }
    </style>
@endSection

@section('links')
    @parent
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
@endSection
