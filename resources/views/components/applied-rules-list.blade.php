<div @class(['mb-4', 'rules-list', 'd-none' => $hidden])>
    <ul class="list-group">
        @if(isset($rules) && count($rules) > 0)
            <table class="table table-sm">
                <thead>
                <tr>
                    <th scope="col">Rule Name</th>
                    <th scope="col">Is Triggered</th>
                    <th scope="col">Is Applied</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($rules as $rule)
                    @php
                        $modalId = 'C-' . Str::uuid()->toString(); // Ensure first character is a letter so that it is a valid HTML ID
                    @endphp
                    <tr>
                        <td><i class="bi bi-gear-fill text-secondary-emphasis"></i>
                            {{ $rule['name'] }}
                            <i role="button" data-bs-toggle="collapse" data-bs-target="#{{ $modalId }}" class="bi bi-info-circle-fill text-info pe-auto"></i>
                            <div class="collapse" id="{{ $modalId }}">
                                <div class="alert alert-info">{{ $rule['description'] }}</div>
                            </div>
                        </td>
                        <td><x-check-mark :checked="$rule['is_triggered']"></x-check-mark></td>
                        <td><x-check-mark :checked="$rule['is_applied']"></x-check-mark></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <li class="list-group-item">No rules are defined</li>
        @endif
    </ul>
</div>
