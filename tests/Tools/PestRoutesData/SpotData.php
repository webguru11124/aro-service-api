<?php

declare(strict_types=1);

namespace Tests\Tools\PestRoutesData;

use Aptive\PestRoutesSDK\Resources\Spots\Spot;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SpotData extends AbstractTestPestRoutesData
{
    public const DATE_FORMAT = 'Y-m-d';
    private const DURATION = 29;
    private const START_TIME = '08:00:00';
    private const TIME_FORMAT = 'H:i:s';

    protected static function getSignature(): array
    {
        return [
            'spotID' => random_int(10000, 99999),
            'officeID' => '1',
            'routeID' => '3662140',
            'date' => Carbon::today()->format(self::DATE_FORMAT),
            'start' => '16:30:00',
            'end' => '16:59:00',
            'spotCapacity' => '29',
            'description' => '04:30',
            'blockReason' => '',
            'currentAppointment' => null,
            'assignedTech' => '0',
            'distanceToPrevious' => '37.976855682979',
            'previousLat' => '34.112831',
            'previousLng' => '-84.673103',
            'prevCustomer' => '2373186',
            'prevSpotID' => '70949264',
            'prevAppointmentID' => '22389239',
            'apiCanSchedule' => '1',
            'open' => '0',
            'lastUpdated' => '2022-08-10 05:18:06',
            'distanceToNext' => '0',
            'nextLat' => '33.8279516',
            'nextLng' => '-84.1065249',
            'nextCustomer' => null,
            'nextSpotID' => '0',
            'nextAppointmentID' => null,
            'reserved' => '0',
            'reservationEnd' => null,
            'appointmentIDs' => [],
            'customerIDs' => [],
            'currentAppointmentDuration' => '0',
            'subscriptionID' => '0',
            'distanceToClosest' => '0',
            'officeTimeZone' => 'PST',
        ];
    }

    protected static function getRequiredEntityClass(): string
    {
        return Spot::class;
    }

    public static function getRawTestData(int $objectsQuantity = 1, array ...$substitutions): Collection
    {
        $data = parent::getRawTestData($objectsQuantity, ...$substitutions)->all();

        $start = self::START_TIME;
        for ($i = 0; $i < $objectsQuantity; $i++) {
            $end = Carbon::parse($start)->addMinutes(self::DURATION)->format(self::TIME_FORMAT);

            if (!isset($substitutions[$i]['start'])) {
                $data[$i]['start'] = $start;
            }

            if (!isset($substitutions[$i]['end'])) {
                $data[$i]['end'] = $end;
            }

            $start = Carbon::parse($end)->addMinute()->format(self::TIME_FORMAT);
        }

        return new Collection($data);
    }
}
