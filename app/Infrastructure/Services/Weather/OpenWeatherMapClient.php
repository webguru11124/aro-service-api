<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Weather;

use Illuminate\Support\Facades\Http;
use App\Infrastructure\Services\Weather\Exceptions\WeatherClientException;
use App\Infrastructure\Services\Weather\Exceptions\WeatherClientConfigurationException;

class OpenWeatherMapClient
{
    private const DEFAULT_UNIT = 'imperial';
    private const RETRIES = 3;
    private const MILLISECONDS_TO_WAIT_BETWEEN_RETRIES = 500;

    public function __construct(
        private string $apiKey,
        private string $apiUrl,
    ) {
    }

    /**
     * @param array<string, string> $query
     *
     * @return array<string, string>
     * @throws WeatherClientException
     * @throws WeatherClientConfigurationException
     */
    public function get(array $query): array
    {
        $this->validateConfigurations();

        try {
            return Http::retry(self::RETRIES, self::MILLISECONDS_TO_WAIT_BETWEEN_RETRIES)
                ->get($this->getUrl($query))
                ->throw()
                ->json();
        } catch (\Exception $e) {
            throw WeatherClientException::instance($e, $query);
        }
    }

    /**
     * @param array<string, string> $query
     *
     * @return string
     */
    private function getUrl(array $query): string
    {
        return $this->apiUrl . '?' . http_build_query(array_merge($query, [
            'units' => self::DEFAULT_UNIT,
            'appid' => $this->apiKey,
        ]));
    }

    private function validateConfigurations(): void
    {
        if (empty($this->apiKey)) {
            throw WeatherClientConfigurationException::missingApiKey();
        }

        if (empty($this->apiUrl)) {
            throw WeatherClientConfigurationException::missingApiUrl();
        }
    }
}
