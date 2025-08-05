<?php

declare(strict_types=1);

return [
    'api_key' => env('OPEN_WEATHER_MAP_API_KEY'),
    'api_url' => env('OPEN_WEATHER_MAP_API_URL', 'https://api.openweathermap.org/data/2.5/forecast'),
];
