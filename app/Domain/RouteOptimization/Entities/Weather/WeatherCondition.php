<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Entities\Weather;

readonly class WeatherCondition
{
    private const THUNDERSTORM = 'Thunderstorm';
    private const DRIZZLE = 'Drizzle';
    private const RAIN = 'Rain';
    private const SNOW = 'Snow';
    private const TORNADO = 'Tornado';
    private const INCLEMENT_WEATHERS = [
        self::THUNDERSTORM => [200, 201, 202, 230, 231, 232],
        self::DRIZZLE => [300, 301, 302, 310, 311, 312, 313, 314, 321],
        self::RAIN => [500, 501, 502, 503, 504, 511, 520, 521, 522, 531],
        self::SNOW => [600, 601, 602, 611, 612, 613, 615, 616, 620, 621, 622],
        self::TORNADO => [781],
    ];

    public function __construct(
        private int|null $id,
        private string|null $main,
        private string|null $description,
        private string|null $iconCode,
    ) {
    }

    /**
     * @return int|null
     */
    public function getId(): int|null
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getMain(): string|null
    {
        return $this->main;
    }

    /**
     * @return string|null
     */
    public function getDescription(): string|null
    {
        return $this->description;
    }

    /**
     * @return string|null
     */
    public function getIconCode(): string|null
    {
        return $this->iconCode;
    }

    /**
     * @return bool
     */
    public function isInclement(): bool
    {
        foreach (self::INCLEMENT_WEATHERS as $ids) {
            if (in_array($this->getId(), $ids)) {
                return true;
            }
        }

        return false;
    }
}
