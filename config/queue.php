<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue API supports an assortment of back-ends via a single
    | API, giving you convenient access to each back-end using the same
    | syntax for every one. Here you may define a default connection.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'sqs'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Laravel. You are free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 3610,
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => 'localhost',
            'queue' => 'default',
            'retry_after' => 90,
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/600580905024/'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
            'retry_after' => 3610,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => false,
        ],
    ],

    'queues' => [
        'route_optimization' => env('SQS_ROUTE_OPTIMIZATION_QUEUE', 'development-01-aro-route-optimization-queue'),
        'schedule-appointments' => env('SCHEDULE_APPOINTMENTS_QUEUE', 'development-01-aro-schedule-appointments-queue'),
        'collect-metrics' => env('COLLECT_METRICS_QUEUE', 'development-01-aro-collect-metrics-queue'),
        'service-stats' => env('SERVICE_STATS_QUEUE', 'development-01-aro-service-stats-queue'),
        'send-notifications' => env('SEND_NOTIFICATIONS_QUEUE', 'development-01-aro-service-send-notifications-queue'),
        'build-reports' => env('BUILD_REPORTS_QUEUE', 'development-01-aro-service-build-reports-queue'),
        'caching' => env('CACHING_QUEUE', 'development-01-aro-service-caching-queue'),
        'routes_creation' => env('ROUTES_CREATION_QUEUE', 'development-01-aro-routes-creation-queue'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'pgsql'),
        'table' => 'field_operations.aro_failed_jobs',
    ],
];
