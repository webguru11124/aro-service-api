<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Notification\Params;

class SmsNotificationParams extends NotificationParams
{
    /**
     * @param string $smsData
     * @param string[] $toNumbers
     * @param string $type
     * @param string $smsBus
     */
    public function __construct(
        public readonly string $smsData,
        public readonly array $toNumbers,
        public readonly string $type = 'sms',
        public readonly string $smsBus = 'internalCommunication',
    ) {
    }
}
