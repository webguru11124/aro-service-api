<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\Translators;

use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Aptive\PestRoutesSDK\Resources\Employees\Employee as PestRoutesEmployee;
use Aptive\PestRoutesSDK\Resources\Routes\Route as PestRoutesRoute;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PestRoutesServiceProTranslator
{
    private const MAX_WORKING_MINS = 630; // 10.5 hours

    public function toDomain(
        PestRoutesRoute $pestRoutesRoute,
        PestRoutesEmployee $pestRoutesEmployee,
        Collection $spotsCollection
    ): ServicePro {
        $skills = array_map(
            fn (string $skillId) => new Skill((int) $skillId),
            array_keys($pestRoutesEmployee->skills)
        );

        $servicePro = new ServicePro(
            id: $pestRoutesEmployee->id,
            name: "$pestRoutesEmployee->firstName $pestRoutesEmployee->lastName",
            startLocation: new Coordinate($pestRoutesEmployee->startLatitude, $pestRoutesEmployee->startLongitude),
            endLocation: new Coordinate($pestRoutesEmployee->startLatitude, $pestRoutesEmployee->startLongitude),
            workingHours: $this->determineWorkingHours($spotsCollection),
            workdayId: (string) $pestRoutesEmployee->employeeLink,
        );

        $servicePro
            ->addSkills($skills)
            ->setRouteId($pestRoutesRoute->id);

        return $servicePro;
    }

    private function determineWorkingHours(Collection $spotsCollection): TimeWindow
    {
        $spotsCollection = $spotsCollection->sortBy('start');
        $start = Carbon::instance($spotsCollection->first()->start);
        $end = $start->clone()->addMinutes(self::MAX_WORKING_MINS);

        return new TimeWindow($start, $end);
    }
}
