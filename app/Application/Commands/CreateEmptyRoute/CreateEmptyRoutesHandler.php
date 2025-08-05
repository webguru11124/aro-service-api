<?php

declare(strict_types=1);

namespace App\Application\Commands\CreateEmptyRoute;

use App\Domain\Contracts\Queries\GetRouteTemplateQuery;
use App\Domain\SharedKernel\Entities\Employee;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Helpers\DateTimeHelper;
use Illuminate\Support\Facades\Log;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\RoutesDataProcessor;
use Aptive\PestRoutesSDK\Resources\Routes\Params\CreateRoutesParams;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class CreateEmptyRoutesHandler
{
    private Office $office;
    private CarbonInterface $date;
    private Collection $employees;

    public function __construct(
        private readonly RoutesDataProcessor $routesDataProcessor,
        private readonly GetRouteTemplateQuery $routeTemplateQuery,
    ) {
    }

    /**
     * Handle the creation of empty route
     *
     * @param CreateEmptyRoutesCommand $command
     *
     * @return void
     */
    public function handle(CreateEmptyRoutesCommand $command): void
    {
        $this->office = $command->office;
        $this->date = $command->date;
        $this->employees = $command->employees;

        $applicableTemplateId = $this->routeTemplateQuery->get($this->office);
        $this->createRoutes($applicableTemplateId);
    }

    /**
     * Creates routes with employee
     *
     * @param int $applicableTemplateId
     *
     * @return void
     */
    private function createRoutes(int $applicableTemplateId): void
    {
        /** @var Employee $employee */
        foreach ($this->employees as $employee) {
            $this->routesDataProcessor->create($this->office->getId(), new CreateRoutesParams(
                date: $this->date->format(DateTimeHelper::DATE_FORMAT),
                templateId: $applicableTemplateId,
                assignedTech: (int) $employee->getEmployeeId() === 0 ? null : (int) $employee->getEmployeeId(),
                autoCreateGroup: true,
                officeId: $this->office->getId(),
            ));

            $this->logRouteCreation($employee);
        }
    }

    private function logRouteCreation(Employee $employee): void
    {
        Log::info(__('messages.route_creation.created'), [
            'service_pro' => $employee->getFullName(),
            'service_pro_id' => $employee->getEmployeeId(),
            'office' => $this->office->getName(),
            'office_id' => $this->office->getId(),
            'date' => $this->date->toDateString(),
        ]);
    }
}
