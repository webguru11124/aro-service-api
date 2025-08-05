<?php

declare(strict_types=1);

namespace Tests\Tools\PestRoutesData;

use Aptive\PestRoutesSDK\Resources\Appointments\Appointment;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentTimeWindow;
use Carbon\Carbon;
use Tests\Tools\TestValue;

class AppointmentData extends AbstractTestPestRoutesData
{
    protected static function getRequiredEntityClass(): string
    {
        return Appointment::class;
    }

    protected static function getSignature(): array
    {
        return [
            'appointmentID' => random_int(10000, 99999),
            'officeID' => '1',
            'customerID' => '2561669',
            'subscriptionID' => '2634064',
            'subscriptionRegionID' => '0',
            'routeID' => '3662041',
            'spotID' => '0',
            'date' => Carbon::now()->addDays(3)->format(TestValue::DATE_FORMAT),
            'start' => '08:00:00',
            'end' => '13:00:00',
            'duration' => '20',
            'type' => ServiceTypeData::RESERVICE,
            'dateAdded' => '2022-07-26 04:13:50',
            'employeeID' => '354931',
            'status' => '0',
            'statusText' => 'Pending',
            'callAhead' => '0',
            'isInitial' => '1',
            'subscriptionPreferredTech' => '0',
            'completedBy' => null,
            'servicedBy' => null,
            'dateCompleted' => null,
            'notes' => null,
            'officeNotes' => null,
            'timeIn' => null,
            'timeOut' => null,
            'checkIn' => null,
            'checkOut' => null,
            'windSpeed' => null,
            'windDirection' => null,
            'temperature' => null,
            'amountCollected' => null,
            'paymentMethod' => null,
            'servicedInterior' => null,
            'ticketID' => null,
            'dateCancelled' => null,
            'additionalTechs' => null,
            'appointmentNotes' => '',
            'doInterior' => '0',
            'dateUpdated' => '2022-07-28 03:18:58',
            'cancelledBy' => null,
            'assignedTech' => '0',
            'latIn' => null,
            'latOut' => null,
            'longIn' => null,
            'longOut' => null,
            'sequence' => '0',
            'lockedBy' => '0',
            'unitIDs' => [],
            'officeTimeZone' => TestValue::CUSTOMER_TIME_ZONE,
            'timeWindow' => AppointmentTimeWindow::Anytime->value,
        ];
    }
}
