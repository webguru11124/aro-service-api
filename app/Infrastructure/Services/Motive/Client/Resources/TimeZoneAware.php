<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources;

use Carbon\CarbonTimeZone;

trait TimeZoneAware
{
    protected \DateTimeZone|null $timeZone = null;

    /**
     * @return string|null
     */
    public function getTimeZone(): string|null
    {
        return $this->timeZone ? $this->transformTimeZone($this->timeZone) : null;
    }

    private function transformTimeZone(\DateTimeZone $timeZone): string|null
    {
        $carbonTimeZone = CarbonTimeZone::create($timeZone);
        $offset = (int) $carbonTimeZone->toOffsetName();

        return match ($offset) {
            -4 => 'Atlantic Time (Canada)',
            -5 => 'Eastern Time (US & Canada)',
            -6 => 'Central Time (US & Canada)',
            -7 => 'Mountain Time (US & Canada)',
            -8 => 'Pacific Time (US & Canada)',
            -9 => 'Alaska',
            default => null,
        };
    }
}
