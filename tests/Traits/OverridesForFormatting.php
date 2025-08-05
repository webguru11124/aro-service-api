<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Entities\WorkEvent\Travel;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\ValueObjects\EndLocation;
use App\Domain\RouteOptimization\ValueObjects\StartLocation;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Carbon\Carbon;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\ServiceProFactory;
use Tests\Tools\Factories\TravelFactory;
use Tests\Tools\Factories\WorkBreakFactory;

trait OverridesForFormatting
{
    protected const APPOINTMENT_DURATION = 25;
    protected const APPOINTMENT_SETUP_DURATION = 5;

    /** @var Appointment[]  */
    protected array $appointments;
    protected EndLocation $endLocation;
    protected WorkBreak $break;
    protected Route $route;
    protected StartLocation $startLocation;
    /** @var Travel[]  */
    protected array $travels;
    protected Appointment $unassignedAppointment;

    protected function getOverrides(): array
    {
        $now = Carbon::now();
        $overrides = [
            'engine' => OptimizationEngine::VROOM,
            'timeFrame' => new TimeWindow(
                $now,
                $now->clone()->addHour()
            ),
        ];
        $this->unassignedAppointment = AppointmentFactory::make();
        $overrides['unassignedAppointments'][] = $this->unassignedAppointment;

        $this->appointments = [
            AppointmentFactory::make([
                'description' => 'Test initial appointment',
            ]),
            AppointmentFactory::make([
                'description' => 'Test regular appointment',
            ]),
        ];
        $this->appointments[0]
            ->setDuration(Duration::fromMinutes(self::APPOINTMENT_DURATION))
            ->setSetupDuration(Duration::fromMinutes(self::APPOINTMENT_SETUP_DURATION));
        $this->appointments[1]
            ->setDuration(Duration::fromMinutes(self::APPOINTMENT_DURATION))
            ->setSetupDuration(Duration::fromMinutes(self::APPOINTMENT_SETUP_DURATION));

        $this->travels = TravelFactory::many(3);
        $this->break = WorkBreakFactory::make();

        /** @var ServicePro $servicePro */
        $servicePro = ServiceProFactory::make();

        $this->startLocation = new StartLocation(
            Carbon::now(),
            $servicePro->getStartLocation(),
        );

        $this->endLocation = new EndLocation(
            Carbon::now()->addHours(8),
            $servicePro->getEndLocation(),
        );

        $this->route = RouteFactory::make(
            [
                'servicePro' => $servicePro,
                'workEvents' => [
                    $this->travels[0],
                    $this->appointments[0],
                    $this->break,
                    $this->travels[1],
                    $this->appointments[1],
                    $this->travels[2],
                    $this->startLocation,
                    $this->endLocation,
                ],
            ]
        );
        $overrides['routes'][] = $this->route;
        $overrides['officeId'] = $this->route->getOfficeId();

        return $overrides;
    }
}
