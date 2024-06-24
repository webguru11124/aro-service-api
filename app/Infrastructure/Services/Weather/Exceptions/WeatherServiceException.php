<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Weather\Exceptions;

use Exception;

class WeatherServiceException extends Exception
{
    /**
     * Throw exception when not allow using service
     *
     * @return self
     */
    public static function notAllowUsingService(int $days): self
    {
        return new self(__('messages.weather.not_allow_using_service', ['days' => $days]));
    }

    /**
     * Throw exception when failed to get response
     *
     * @param string $rawData
     *
     * @return self
     */
    public static function failedToGetResponse(string $rawData): self
    {
        return new self(__('messages.weather.failed_to_get_response', ['data' => $rawData]));
    }
}
