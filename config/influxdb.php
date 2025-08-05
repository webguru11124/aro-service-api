<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Influx DB Client
    |--------------------------------------------------------------------------
    |
    | This is the configuration for connecting to an influxDB database via the
    | php client
    |
    */

    'connection' => [
        'host' => env('INFLUXDB_HOST', 'aro-influxdb:8086'),
        'organization' => env('INFLUXDB_ORGANIZATION', 'Application Metrics'),
        'bucket' => env('INFLUXDB_BUCKET', 'aro_service'),
        'token' => env('INFLUXDB_TOKEN'),
    ],
];
