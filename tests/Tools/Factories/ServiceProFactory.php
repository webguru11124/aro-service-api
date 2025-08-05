<?php

declare(strict_types=1);

namespace Tests\Tools\Factories;

use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Tests\Tools\TestValue;

class ServiceProFactory extends AbstractFactory
{
    public const WORKING_HOURS = 8;

    public function single($overrides = []): ServicePro
    {
        $id = $overrides['id'] ?? $this->faker->randomNumber(6);
        $routeId = $overrides['routeId'] ?? TestValue::ROUTE_ID;
        $skills = $overrides['skills'] ?? [new Skill(Skill::AA)];

        return (new ServicePro(
            id: $id,
            name: $overrides['name'] ?? $this->faker->firstName() . ' ' . $this->faker->lastName(),
            startLocation: $overrides['startLocation'] ?? new Coordinate(
                $this->faker->latitude(TestValue::MIN_LATITUDE, TestValue::MAX_LATITUDE),
                $this->faker->longitude(TestValue::MIN_LONGITUDE, TestValue::MAX_LONGITUDE),
            ),
            endLocation: $overrides['endLocation'] ?? new Coordinate(
                $this->faker->latitude(TestValue::MIN_LATITUDE, TestValue::MAX_LATITUDE),
                $this->faker->longitude(TestValue::MIN_LONGITUDE, TestValue::MAX_LONGITUDE),
            ),
            workingHours: $overrides['workingHours'] ?? new TimeWindow(
                Carbon::tomorrow(),
                Carbon::tomorrow()->addHours(self::WORKING_HOURS)
            ),
            workdayId: array_key_exists('workdayId', $overrides) ? $overrides['workdayId'] : $this->faker->text(7),
            avatarBase64: $overrides['avatarBase64'] ?? $this->faker->text(7),
        ))
            ->setRouteId($routeId)
            ->addSkills($skills);
    }
}
