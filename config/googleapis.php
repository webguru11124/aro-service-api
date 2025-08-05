<?php

declare(strict_types=1);

return [
    'auth' => [
        'type' => 'service_account',
        'project_id' => getenv('GOOGLEAPIS_PROJECT_ID'),
        'private_key_id' => getenv('GOOGLEAPIS_PRIVATE_KEY_ID'),
        'private_key' => getenv('GOOGLEAPIS_PRIVATE_KEY'),
        'client_email' => getenv('GOOGLEAPIS_CLIENT_EMAIL'),
        'client_id' => getenv('GOOGLEAPIS_CLIENT_ID'),
        'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
        'token_uri' => 'https://oauth2.googleapis.com/token',
        'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
        'client_x509_cert_url' => getenv('GOOGLEAPIS_CLIENT_CERT_URL'),
        'universe_domain' => 'googleapis.com',
    ],
    'grpc_enabled' => env('ENABLE_GRPC', 0),
];
