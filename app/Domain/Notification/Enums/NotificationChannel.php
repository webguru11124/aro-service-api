<?php

declare(strict_types=1);

namespace App\Domain\Notification\Enums;

enum NotificationChannel: string
{
    case SMS = 'sms';
    case EMAIL = 'email';

    /**
     * It returns all the values of the enum.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
