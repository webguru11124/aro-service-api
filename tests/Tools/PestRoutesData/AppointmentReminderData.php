<?php

declare(strict_types=1);

namespace Tests\Tools\PestRoutesData;

use Aptive\PestRoutesSDK\Resources\AppointmentReminders\AppointmentReminder;

class AppointmentReminderData extends AbstractTestPestRoutesData
{
    protected static function getRequiredEntityClass(): string
    {
        return AppointmentReminder::class;
    }

    protected static function getSignature(): array
    {
        return [
            'reminderID' => random_int(10000, 99999),
            'officeID' => '1',
            'appointmentID' => random_int(10000, 99999),
            'text' => '',
            'dateSent' => '2022-11-09 06:46:53',
            'emailSent' => '2022-11-09 06:47:09',
            'voiceSent' => '2022-11-09 06:47:28',
            'status' => '6',
            'response' => '',
            'responseTime' => '0000-00-00 00:00:00',
            'sendTo' => '9195979351',
            'emailAddress' => 'test_customer@example.com',
            'voiceNumber' => '9195979351',
            'dateUpdated' => '2022-11-09 06:47:28',
        ];
    }
}
