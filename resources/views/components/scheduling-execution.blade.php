<div>
    <a class="badge bg-success" href="{{ $link }}">{{ $createdAt }}</a>
    @if($stats)
        <span class="badge bg-secondary">{{ $stats['routes_count'] }}: {{ $stats['scheduled_services_count'] }}/{{ $stats['pending_services_count'] }}</span>
    @endif
</div>
