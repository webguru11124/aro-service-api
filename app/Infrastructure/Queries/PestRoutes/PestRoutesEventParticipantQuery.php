<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\PestRoutes;

use App\Domain\Calendar\Entities\Event;
use App\Domain\Calendar\Entities\Participant;
use App\Domain\Contracts\Queries\EventParticipantQuery;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesEmployeesDataProcessor;
use Aptive\PestRoutesSDK\Resources\Employees\Employee as PestRoutesEmployee;
use Aptive\PestRoutesSDK\Resources\Employees\Params\SearchEmployeesParams;
use Aptive\PestRoutesSDK\Resources\Employees\EmployeeType;
use Illuminate\Support\Collection;

class PestRoutesEventParticipantQuery implements EventParticipantQuery
{
    public function __construct(
        private readonly PestRoutesEmployeesDataProcessor $pestRoutesEmployeesDataProcessor,
    ) {
    }

    /**
     * Fetches participants for a given event.
     *
     * @param Event $event
     *
     * @return Collection<Participant> of Participant entities
     */
    public function find(Event $event): Collection
    {
        $officeId = $event->getOfficeId();

        $officeStaff = $this->pestRoutesEmployeesDataProcessor->extract($officeId, new SearchEmployeesParams(
            officeIds: [$officeId],
            isActive: true,
            type: EmployeeType::OfficeStaff,
        ));

        $technicians = $this->pestRoutesEmployeesDataProcessor->extract($officeId, new SearchEmployeesParams(
            officeIds: [$officeId],
            isActive: true,
            type: EmployeeType::Technician,
        ));

        $participants = $officeStaff->merge($technicians);

        $invitedParticipantsIds = $event->getParticipantIds();

        $participants = $participants->map(function (PestRoutesEmployee $employee) use ($invitedParticipantsIds) {
            return new Participant(
                $employee->id,
                $employee->firstName . ' ' . $employee->lastName,
                $invitedParticipantsIds->contains($employee->id),
                $employee->employeeLink ?: null,
            );
        });

        $sortedParticipants = $participants->sortBy->getName();

        return $sortedParticipants;
    }
}
