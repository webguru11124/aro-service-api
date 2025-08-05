<?php

declare(strict_types=1);

namespace App\Domain\Calendar\Enums;

enum WeekDay: string
{
    case MONDAY = 'monday';
    case TUESDAY = 'tuesday';
    case WEDNESDAY = 'wednesday';
    case THURSDAY = 'thursday';
    case FRIDAY = 'friday';
    case SATURDAY = 'saturday';
    case SUNDAY = 'sunday';

    /**
     * @return WeekDay[]
     */
    public static function getOrder(): array
    {
        return [
            self::MONDAY,
            self::TUESDAY,
            self::WEDNESDAY,
            self::THURSDAY,
            self::FRIDAY,
            self::SATURDAY,
            self::SUNDAY,
        ];
    }

    /**
     * Returns the day number in a week
     *
     * @return int
     */
    public function getIndex(): int
    {
        return array_search($this, self::getOrder());
    }
}
