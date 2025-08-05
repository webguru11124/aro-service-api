<?php

declare(strict_types=1);

namespace App\View\Shared;

use Carbon\Carbon;

trait TimelineEventAttributes
{
    private const DURATION_MULTIPLIER = 4;

    private function getEventParams(array $event): array
    {
        $startAt = Carbon::parse($event['scheduled_time_window']['start']);
        $endAt = Carbon::parse($event['scheduled_time_window']['end']);
        $duration = $startAt->diffInMinutes($endAt);

        return [
            'title' => $event['description'] ?? '',
            'startAt' => $startAt->format('H:i'),
            'duration' => $duration,
            'width' => $duration * self::DURATION_MULTIPLIER,
        ];
    }
}
