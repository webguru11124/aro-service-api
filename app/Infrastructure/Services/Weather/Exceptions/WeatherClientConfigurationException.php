<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Weather\Exceptions;

use Exception;

class WeatherClientConfigurationException extends Exception
{
    /**
     * @return self
     */
    public static function missingApiKey(): self
    {
        return new self(__('messages.weather.api_key_is_empty'));
    }

    /**
     * @return self
     */
    public static function missingApiUrl(): self
    {
        return new self(__('messages.weather.api_url_is_empty'));
    }
}
