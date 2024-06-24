<?php

declare(strict_types=1);

namespace App\Application\Jobs;

use App\Application\Commands\CreateEmptyRoute\CreateEmptyRoutesCommand;
use App\Application\Commands\CreateEmptyRoute\CreateEmptyRoutesHandler;
use App\Application\Events\RoutesCreationJob\RoutesCreationJobEnded;
use App\Application\Events\RoutesCreationJob\RoutesCreationJobFailed;
use App\Application\Events\RoutesCreationJob\RoutesCreationJobStarted;
use App\Domain\Contracts\Queries\EmployeeInfoQuery;
use App\Domain\Contracts\Queries\GetRoutesByOfficeAndDateQuery;
use App\Domain\Contracts\Queries\Params\EmployeeInfoQueryParams;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\NoServiceProFoundException;
use App\Infrastructure\Exceptions\RouteTemplatesNotFoundException;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Performs the creation of routes within an office
 */
class RoutesCreationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60; // 1 hour

    private GetRoutesByOfficeAndDateQuery $getRoutesByOfficeAndDateQuery;
    private EmployeeInfoQuery $employeeInfoQuery;
    private CreateEmptyRoutesHandler $CreateEmptyRoutesHandler;

    private Collection $employees;

    public function __construct(
        public readonly CarbonInterface $date,
        public readonly Office $office,
    ) {
        $this->onQueue(config('queue.queues.routes_creation'));
    }

    /**
     * Execute the job.
     */
    public function handle(
        GetRoutesByOfficeAndDateQuery $getRoutesByOfficeAndDateQuery,
        EmployeeInfoQuery $employeeInfoQuery,
        CreateEmptyRoutesHandler $CreateEmptyRoutesHandler,
    ): void {
        $this->getRoutesByOfficeAndDateQuery = $getRoutesByOfficeAndDateQuery;
        $this->employeeInfoQuery = $employeeInfoQuery;
        $this->CreateEmptyRoutesHandler = $CreateEmptyRoutesHandler;

        RoutesCreationJobStarted::dispatch($this->office, $this->date, $this->job);

        try {
            if ($this->getRoutesByOfficeAndDateQuery->get($this->office, $this->date)->isNotEmpty()) {
                $this->logRoutesAlreadyExist();
            } else {
                $this->getAvailableEmployeeForOfficeAndDate();
                $this->processRoutesCreation();
            }
        } catch (NoServiceProFoundException|RouteTemplatesNotFoundException $e) {
            Log::notice($e->getMessage());
        }

        RoutesCreationJobEnded::dispatch($this->office, $this->date, $this->job);
    }

    /**
     * Fetches the available employees for the office and date
     *
     * @throws NoServiceProFoundException
     */
    private function getAvailableEmployeeForOfficeAndDate(): void
    {
        //TODO: use params to fetch employees available for the specific date using the repository when it will be possible
        $this->employees = $this->employeeInfoQuery->get(new EmployeeInfoQueryParams());

        if ($this->employees->isEmpty()) {
            throw NoServiceProFoundException::instance($this->office->getId(), $this->office->getName(), $this->date);
        }
    }

    /**
     * Processes the creation of routes
     */
    private function processRoutesCreation(): void
    {
        $this->CreateEmptyRoutesHandler->handle(new CreateEmptyRoutesCommand($this->office, $this->date, $this->employees));
    }

    private function logRoutesAlreadyExist(): void
    {
        Log::notice(__('messages.route_creation.routes_already_exist'), [
            'office' => $this->office->getName(),
            'office_id' => $this->office->getId(),
            'date' => $this->date->toDateString(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        RoutesCreationJobFailed::dispatch($this->office, $this->date, $this->job, $exception);
    }
}
