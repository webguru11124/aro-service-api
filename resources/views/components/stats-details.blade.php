<table class="table table-sm">
    <thead>
    <tr>
        <th scope="col">Summary</th>
        <th scope="col">Value</th>
    </tr>
    </thead>
    <tbody>
    @foreach($statsDetails as $summary => $value)
        <tr>
            <td>{{ ucwords(str_replace('_', ' ', $summary)) }}</td>
            <td>{{ round($value, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

