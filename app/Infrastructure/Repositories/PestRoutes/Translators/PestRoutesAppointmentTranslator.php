<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\Translators;

use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use Aptive\PestRoutesSDK\Resources\AppointmentReminders\AppointmentReminder as PestRoutesAppointmentReminder;
use Aptive\PestRoutesSDK\Resources\Appointments\Appointment as PestRoutesAppointment;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentTimeWindow;
use Aptive\PestRoutesSDK\Resources\Customers\Customer as PestRoutesCustomer;
use Aptive\PestRoutesSDK\Resources\ServiceTypes\ServiceType as PestRoutesServiceType;
use Aptive\PestRoutesSDK\Resources\Spots\Spot as PestRoutesSpot;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PestRoutesAppointmentTranslator
{
    /**
     * @param PestRoutesAppointment $pestRoutesAppointment
     * @param PestRoutesCustomer $pestRoutesCustomer
     * @param PestRoutesServiceType $pestRoutesServiceType
     * @param PestRoutesSpot|null $spot
     * @param Collection<PestRoutesAppointmentReminder>|null $pestRoutesAppointmentReminders
     *
     * @return Appointment
     */
    public function toDomain(
        PestRoutesAppointment $pestRoutesAppointment,
        PestRoutesCustomer $pestRoutesCustomer,
        PestRoutesServiceType $pestRoutesServiceType,
        PestRoutesSpot|null $spot,
        Collection|null $pestRoutesAppointmentReminders,
    ): Appointment {
        $id = $pestRoutesAppointment->id;
        $coordinate = new Coordinate($pestRoutesCustomer->latitude, $pestRoutesCustomer->longitude);
        $description = $pestRoutesServiceType->description;
        $skills = $this->getSkills($pestRoutesCustomer, $pestRoutesServiceType);
        $appointmentNotified = !empty($pestRoutesAppointmentReminders);

        $appointment = new Appointment(
            $id,
            $description,
            $coordinate,
            $appointmentNotified,
            $pestRoutesAppointment->officeId,
            $pestRoutesAppointment->customerId,
            $pestRoutesCustomer->preferredTechId,
            $skills,
        );
        $appointment
            ->setRouteId($pestRoutesAppointment->routeId)
            ->setDuration(Duration::fromMinutes($pestRoutesAppointment->duration))
            ->setExpectedArrival($this->getExpectedAppointmentArrival($pestRoutesAppointment))
            ->setTimeWindow($this->resolveAppointmentTimeWindow($pestRoutesAppointment, $spot));

        return $appointment;
    }

    /**
     * @param PestRoutesCustomer $pestRoutesCustomer
     * @param PestRoutesServiceType $pestRoutesServiceType
     *
     * @return Collection<Skill>
     */
    private function getSkills(
        PestRoutesCustomer $pestRoutesCustomer,
        PestRoutesServiceType $pestRoutesServiceType
    ): Collection {
        $skills = new Collection();

        $this->addStateSkill($skills, $pestRoutesCustomer);
        $this->addServiceTypeSkill($skills, $pestRoutesServiceType);

        return $skills;
    }

    private function addServiceTypeSkill(Collection $skills, PestRoutesServiceType $pestRoutesServiceType): void
    {
        if ($pestRoutesServiceType->isInitial) {
            $skills->add(new Skill(Skill::INITIAL_SERVICE));
        }
    }

    private function addStateSkill(Collection $skills, PestRoutesCustomer $pestRoutesCustomer): void
    {
        $state = $pestRoutesCustomer->address->state ?? $pestRoutesCustomer->billingInformation->address->state ?? null;

        if ($state === null) {
            Log::notice('No state found for customer', [
                'customer_id' => $pestRoutesCustomer->id,
                'customer_name' => $pestRoutesCustomer->firstName . ' ' . $pestRoutesCustomer->lastName,
            ]);

            return;
        }

        $stateSkill = Skill::tryFromState($state);

        if ($stateSkill !== null) {
            $skills->add($stateSkill);
        }
    }

    private function getExpectedAppointmentArrival(PestRoutesAppointment $pestRoutesAppointment): TimeWindow
    {
        $start = Carbon::instance($pestRoutesAppointment->start);
        $end = Carbon::instance($pestRoutesAppointment->end);

        if ($start->greaterThan($end)) {
            $end = $end->endOfDay();
        }

        return match ($pestRoutesAppointment->timeWindow) {
            AppointmentTimeWindow::AM => new TimeWindow(
                $start->startOfDay(),
                $end->midDay()
            ),
            AppointmentTimeWindow::PM => new TimeWindow(
                $start->midDay(),
                $end->endOfDay()
            ),
            AppointmentTimeWindow::Timed => new TimeWindow(
                $start,
                $end
            ),
            default => new TimeWindow(
                $start->startOfDay(),
                $end->endOfDay()
            ),
        };
    }

    private function resolveAppointmentTimeWindow(PestRoutesAppointment $appointment, PestRoutesSpot|null $spot): TimeWindow
    {
        if ($spot === null) {
            return new TimeWindow(
                Carbon::instance($appointment->start),
                Carbon::instance($appointment->end)
            );
        }

        return new TimeWindow(
            Carbon::instance($spot->start),
            Carbon::instance($spot->end)
        );
    }
}
