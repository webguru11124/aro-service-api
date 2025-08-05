<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Weather\Exceptions;

use Exception;

class WeatherClientException extends Exception
{
    /**
     * @param Exception $exception
     * @param array<string, string> $query
     *
     * @return self
     */
    public static function instance(Exception $exception, array $query): self
    {
        return new self(__(
            'messages.weather.failed_to_get_weather',
            [
                'query' => json_encode($query),
                'error' => $exception->getMessage(),
            ],
        ));
    }
}
