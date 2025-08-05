<?php

declare(strict_types=1);

namespace App\Domain\SharedKernel\ValueObjects;

use Carbon\Carbon;

class Timezone
{
    private const ABBREVIATIONS = [
        'AST' => 'Atlantic Standard Time',
        'EST' => 'Eastern Standard Time',
        'EDT' => 'Eastern Daylight Time',
        'CST' => 'Central Standard Time',
        'CDT' => 'Central Daylight Time',
        'MST' => 'Mountain Standard Time',
        'MDT' => 'Mountain Daylight Time',
        'PST' => 'Pacific Standard Time',
        'PDT' => 'Pacific Daylight Time',
        'UTC' => 'Coordinated Universal Time',
    ];

    public function __construct(
        private string $timezone,
    ) {
    }

    /**
     * Returns the full name of the timezone.
     *
     * @return string
     */
    public function getTimezoneFullName(): string
    {
        $abbreviation = Carbon::now($this->timezone)->isoFormat('z');

        return self::ABBREVIATIONS[$abbreviation] ?? self::ABBREVIATIONS['UTC'];
    }

    /**
     * @return string
     */
    public function getTimezone(): string
    {
        return $this->timezone;
    }
}
