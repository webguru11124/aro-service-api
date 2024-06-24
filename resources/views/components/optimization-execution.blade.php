<div>
    <a @class(['badge', 'bg-success' => $success, 'bg-warning text-dark' => !$success]) href="{{ $link }}">{{ $createdAt }}</a>
    @if($success)
        <x-optimization-score :score="$score"></x-optimization-score>
        <span class="badge bg-secondary">{{ $stats['total_routes'] }}: {{ $stats['total_assigned_appointments'] }}/{{ $stats['total_unassigned_appointments'] }}</span>
    @endif
</div>
