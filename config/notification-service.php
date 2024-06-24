<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Notification Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the Notification Service, responsible for
    | sending automated messages and alerts. Includes API authentication,
    | endpoint details, and recipient settings for different notification scenarios.
    |
    */

    'auth' => [
        'api_url' => env('NOTIFICATION_SERVICE_API_URL', 'https://mvz2mj9ny0.execute-api.us-east-1.amazonaws.com/dev/api/v1/send-message'),
        'api_bearer_token' => env('NOTIFICATION_SERVICE_API_BEARER_TOKEN'),
    ],
    'recipients' => [
        'from_email' => env('NOTIFICATION_SERVICE_FROM_EMAIL', 'aro_no_reply@goaptive.com'),
    ],
    'slack' => [
        'aro_notifications_app_webhook_url' => env('NOTIFICATION_SERVICE_SLACK_WEBHOOK_URL'),
    ],
];
