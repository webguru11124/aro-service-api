<?php

declare(strict_types=1);

namespace App\Application\Http\Web\SchedulingOverview\Controllers;

use App\Application\Http\Web\SchedulingOverview\Requests\SchedulingDebugRequest;
use App\Application\Http\Web\SchedulingOverview\Requests\SchedulingExecutionsRequest;
use App\Application\Http\Web\SchedulingOverview\Requests\SchedulingExportRequest;
use App\Application\Http\Web\SchedulingOverview\Requests\SchedulingMapRequest;
use App\Application\Http\Web\SchedulingOverview\Requests\SchedulingOverviewRequest;
use App\Domain\Contracts\Queries\Office\GetAllOfficesQuery;
use App\Domain\Contracts\Repositories\SchedulingStateRepository;
use App\Domain\Scheduling\Factories\SchedulingDebugStateFactory;
use App\Domain\Scheduling\Services\AppointmentSchedulingService;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Services\SchedulingDataService;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SchedulingOverviewController extends Controller
{
    private const DEFAULT_OFFICE_ID = 39;

    public function __construct(
        private SchedulingDataService $schedulingDataService,
        private GetAllOfficesQuery $officesQuery,
        private SchedulingDebugStateFactory $debugStateFactory,
        private AppointmentSchedulingService $schedulingService,
        private SchedulingStateRepository $schedulingStateRepository,
    ) {
    }

    /**
     * GET /scheduling/overview
     */
    public function schedulingOverview(SchedulingOverviewRequest $request): View
    {
        $schedulingDate = !empty($request->scheduling_date)
            ? Carbon::parse($request->scheduling_date)
            : Carbon::now();

        $executionDate = !empty($request->execution_date)
            ? Carbon::parse($request->execution_date)
            : null;

        $officeId = !empty($request->office_id) ? (int) $request->office_id : self::DEFAULT_OFFICE_ID;

        $statesGroupedByDate = $this->schedulingDataService->getSchedulingOverview($officeId, $schedulingDate, $executionDate);
        $createdAtDates = $statesGroupedByDate->keys()->toArray();

        if (is_null($executionDate) && !empty($createdAtDates)) {
            $executionDate = Carbon::parse($createdAtDates[0]);
        }

        return view('scheduling-overview', [
            'title' => 'Scheduling Overview',
            'schedulingDate' => $schedulingDate->toDateString(),
            'executionDate' => $executionDate?->toDateString(),
            'prevDate' => $schedulingDate->clone()->subDay()->toDateString(),
            'nextDate' => $schedulingDate->clone()->addDay()->toDateString(),
            'selectedOfficeId' => $officeId,
            'offices' => $this->getOffices(),
            'createdAtDates' => $createdAtDates,
            'states' => $statesGroupedByDate,
        ]);
    }

    /**
     * GET /scheduling/executions
     *
     * @param SchedulingExecutionsRequest $request
     *
     * @return View
     */
    public function schedulingExecutions(SchedulingExecutionsRequest $request): View
    {
        $date = !empty($request->execution_date)
            ? Carbon::parse($request->execution_date)
            : Carbon::now();

        $executions = $this->schedulingDataService->getSchedulingExecutions($date);
        $scheduledDates = $executions->pluck('as_of_date')->unique();
        $results = $executions
            ->groupBy('office_id')
            ->map(fn (Collection $group) => $group->groupBy('as_of_date'));

        return view('scheduling-executions', [
            'title' => 'Scheduling Executions',
            'selectedDate' => $date->toDateString(),
            'prevDate' => $date->clone()->subDay()->toDateString(),
            'nextDate' => $date->clone()->addDay()->toDateString(),
            'dates' => $scheduledDates,
            'executions' => $results,
            'offices' => $this->getOffices(),
        ]);
    }

    /**
     * GET /scheduling/map
     */
    public function schedulingMap(SchedulingMapRequest $request): View
    {
        $state = $this->schedulingDataService->getStateData((int) $request->state_id);

        return view('scheduling-map', [
            'title' => 'Scheduling Map',
            'state' => $state,
        ]);
    }

    /**
     * GET /scheduling/export
     */
    public function schedulingExport(SchedulingExportRequest $request): Response
    {
        $state = $this->schedulingDataService->getStateData((int) $request->state_id);

        return response(json_encode($state, JSON_PRETTY_PRINT))
            ->header('Content-Type', 'application/json');
    }

    /**
     * POST /scheduling/debug
     */
    public function schedulingDebug(SchedulingDebugRequest $request): Response
    {
        $state = json_decode($request->state, true);

        $schedulingState = $this->debugStateFactory->create($state);
        $newSchedulingState = $this->schedulingService->schedulePendingServices($schedulingState);

        $this->schedulingStateRepository->save($newSchedulingState);

        $result = $this->schedulingDataService->getStateData($newSchedulingState->getId());

        return response(json_encode($result, JSON_PRETTY_PRINT))
            ->header('Content-Type', 'application/json');
    }

    private function getOffices(): Collection
    {
        return $this->officesQuery
            ->get()
            ->sortBy(fn (Office $office) => $office->getName())
            ->mapWithKeys(fn (Office $office) => [$office->getId() => $office->getName()]);
    }
}
