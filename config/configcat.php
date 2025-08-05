<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Config Cat Auth
    |--------------------------------------------------------------------------
    |
    | This is the configuration for the configCat sdk client. This client
    | is for interacting with the configCat feature flag system. An SDK
    | key is used for authentication to a specific environment and is also
    | used internally by the package to determine the correct url
    |
    */

    'auth' => [
        'sdk_key' => env('CONFIGCAT_SDK_KEY'),
    ],
];
