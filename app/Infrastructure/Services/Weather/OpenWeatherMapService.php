<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Weather;

use App\Infrastructure\Services\Weather\Exceptions\WeatherClientConfigurationException;
use App\Infrastructure\Services\Weather\Exceptions\WeatherClientException;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use App\Domain\Contracts\Services\WeatherService;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\RouteOptimization\Entities\Weather\WeatherInfo;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Services\Weather\Translators\WeatherInfoTranslator;
use App\Infrastructure\Services\Weather\Exceptions\WeatherServiceException;

class OpenWeatherMapService implements WeatherService
{
    private const MAX_DAYS_FORECAST = 5;
    private const MAX_CNT_VALUE = 40;

    public function __construct(
        private OpenWeatherMapClient $client,
        private WeatherInfoTranslator $weatherInfoTranslator,
    ) {
    }

    /**
     * Get current weather by coordinates
     *
     * @param Office $office
     * @param CarbonInterface $date
     * @param Coordinate $location
     *
     * @return WeatherInfo
     * @throws WeatherServiceException
     * @throws WeatherClientException
     * @throws WeatherClientConfigurationException
     */
    public function getCurrentWeatherByCoordinates(
        Office $office,
        CarbonInterface $date,
        Coordinate $location,
    ): WeatherInfo {
        if (!$this->isAllowedToGetWeatherForecast($date)) {
            throw WeatherServiceException::notAllowUsingService(self::MAX_DAYS_FORECAST);
        }

        return $this->getWeatherForecast(
            [
                'lat' => $location->getLatitude(),
                'lon' => $location->getLongitude(),
                'cnt' => $this::MAX_CNT_VALUE,
            ],
            $date,
            $office,
        );
    }

    /**
     * @param array<string, mixed> $query
     * @param CarbonInterface $date
     * @param Office $office
     *
     * @return WeatherInfo
     * @throws WeatherClientException
     * @throws WeatherServiceException
     * @throws WeatherClientConfigurationException
     */
    private function getWeatherForecast(array $query, CarbonInterface $date, Office $office): WeatherInfo
    {
        $officeId = $office->getId();
        $rawData = $this->client->get($query);

        if (empty($rawData)) {
            throw WeatherServiceException::failedToGetResponse(json_encode($rawData));
        }

        $filteredData = $this->filterWeatherRawData($rawData, $date);

        return $this->weatherInfoTranslator->toDomain($filteredData, $date, $officeId);
    }

    private function isAllowedToGetWeatherForecast(CarbonInterface $date): bool
    {
        return $date->isToday() || ($date->diffInDays() < self::MAX_DAYS_FORECAST && $date->isFuture());
    }

    /**
     * @param array<string, mixed> $data
     * @param CarbonInterface $date
     *
     * @return array<string, mixed>
     */
    private function filterWeatherRawData(array $data, CarbonInterface $date): array
    {
        $filtered = array_values(array_filter($data['list'], function (array $item) use ($date) {
            $forecastTime = Carbon::createFromTimestamp($item['dt']);

            return $forecastTime->isSameDay($date) && $forecastTime->hour >= 12;
        }));

        return $filtered[0] ?? [];
    }
}
