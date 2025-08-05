<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Services;

use Carbon\CarbonInterface;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\RouteOptimization\Entities\Weather\WeatherInfo;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Services\Weather\Exceptions\WeatherServiceException;

interface WeatherService
{
    /**
     * Get current weather by coordinates
     *
     * @param Office $office
     * @param CarbonInterface $date
     * @param Coordinate $location
     *
     * @return WeatherInfo
     * @throws WeatherServiceException
     */
    public function getCurrentWeatherByCoordinates(
        Office $office,
        CarbonInterface $date,
        Coordinate $location,
    ): WeatherInfo;
}
