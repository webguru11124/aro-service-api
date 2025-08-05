<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Vroom Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration section is for configuring communication with the vroom
    | instance this service uses for route optimization
    |
    */

    'connection' => [
        'url' => env('VROOM_URL', 'aro-vroom:3000'),
        'retries' => env('VROOM_RETRIES', 3),
        'milliseconds_to_wait_between_retries' => env('VROOM_MILLISECONDS_TO_WAIT_BETWEEN_RETRIES', 10000),
    ],
];
