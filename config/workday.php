<?php

declare(strict_types=1);

return [
    'auth' => [
        'isu_username' => env('WORKDAY_ISU_USERNAME', 'ISU_ARO'),
        'client_id' => env('WORKDAY_CLIENT_ID'),
        'private_key' => base64_decode(env('WORKDAY_PRIVATE_KEY_BASE64', '')),
        'access_token_url' => env('WORKDAY_ACCESS_TOKEN_URL', 'https://services1.myworkday.com/ccx/oauth2/aptive/token'),
        'access_token_cached_for_seconds' => env('WORKDAY_ACCESS_TOKEN_CACHED_FOR_SECONDS', 270),
    ],
    'services' => [
        'human_resources_url' => env('WORKDAY_HUMAN_RESOURCES_URL', 'https://services1.myworkday.com/ccx/service/aptive/Human_Resources/v39.1'),
        'financial_report_url' => env('WORKDAY_FINANCIAL_REPORT_URL', 'https://services1.myworkday.com/ccx/service/customreport2/aptive/hailey.orton/BI___Gross_Margin'),
        'service_pro_info_report_url' => env('WORKDAY_SERVICE_PRO_INFO_REPORT_URL', 'https://services1.myworkday.com/ccx/service/customreport2/aptive/ISU_ARO/Service_Pro_Photos_and_Basic_Info'),
    ],
];
