<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Pestroutes SDK
    |--------------------------------------------------------------------------
    |
    | These options control the configuration needed for the pestroutes SDK
    |
    */

    'url' => env('PESTROUTES_URL', 'https://demoawsaptivepest.pestroutes.com/api/'),
    'credentials' => [
        'dynamo_db_table' => env('DYNAMO_DB_OFFICE_CREDENTIALS_TABLENAME', 'development-01.testing-office-credentials-api-pestroutes.dynamodb_table'),
    ],
];
