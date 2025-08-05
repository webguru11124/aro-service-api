<table class="table table-sm">
    <thead>
    <tr>
        <th scope="col">Weather Info</th>
        <th scope="col">Value</th>
    </tr>
    </thead>
    <tbody>
    @foreach($weatherForecast as $summary => $value)
        <tr>
            <td>{{ ucwords(str_replace('_', ' ', $summary)) }}</td>
            <td>{{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

