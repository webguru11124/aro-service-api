<?php

declare(strict_types=1);

namespace App\Application\Http\Web\OptimizationOverview\Controllers;

use App\Application\Http\Web\OptimizationOverview\Requests\OptimizationDetailsRequest;
use App\Application\Http\Web\OptimizationOverview\Requests\OptimizationExecutionsRequest;
use App\Application\Http\Web\OptimizationOverview\Requests\OptimizationMapRequest;
use App\Application\Http\Web\OptimizationOverview\Requests\OptimizationOverviewRequest;
use App\Application\Http\Web\OptimizationOverview\Requests\OptimizationSandboxRequest;
use App\Application\Http\Web\OptimizationOverview\Requests\OptimizationSimulationRequest;
use App\Application\Http\Web\OptimizationOverview\Requests\OptimizationSimulationRunRequest;
use App\Application\Jobs\OptimizeRoutesSimulationJob;
use App\Domain\Contracts\Queries\Office\GetAllOfficesQuery;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Formatters\RouteArrayFormatter;
use App\Infrastructure\Services\OptimizationDataService;
use App\Infrastructure\Services\OptimizationSandboxDataService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class OptimizationOverviewController extends Controller
{
    private const DEFAULT_OFFICE_ID = 39;

    public function __construct(
        private OptimizationDataService $optimizationDataService,
        private OptimizationSandboxDataService $optimizationSandboxService,
        private GetAllOfficesQuery $officesQuery,
        private RouteArrayFormatter $routeArrayFormatter,
    ) {
    }

    /**
     * GET /optimization/executions
     */
    public function optimizationExecutions(OptimizationExecutionsRequest $request): View
    {
        $date = !empty($request->execution_date)
            ? Carbon::parse($request->execution_date)
            : Carbon::now();

        $results = $this->optimizationDataService->getOptimizationExecutions($date);

        $optimizedDates = $results->pluck('as_of_date')->unique();
        $results = $results->groupBy('office_id')
            ->map(fn (Collection $group) => $group->groupBy('as_of_date'));

        return view('optimization-executions', [
            'title' => 'Optimization Executions',
            'selectedDate' => $date->toDateString(),
            'prevDate' => $date->clone()->subDay()->toDateString(),
            'nextDate' => $date->clone()->addDay()->toDateString(),
            'dates' => $optimizedDates,
            'executions' => $results,
            'offices' => $this->getOffices(),
        ]);
    }

    /**
     * GET /optimization/overview
     */
    public function optimizationOverview(OptimizationOverviewRequest $request): View
    {
        $optimizationDate = !empty($request->optimization_date)
            ? Carbon::parse($request->optimization_date)
            : Carbon::now();

        $executionDate = !empty($request->execution_date)
            ? Carbon::parse($request->execution_date)
            : null;

        $officeId = !empty($request->office_id) ? (int) $request->office_id : self::DEFAULT_OFFICE_ID;

        $statesGroupedByDate = $this->optimizationDataService->getOptimizationOverview($officeId, $optimizationDate, $executionDate);
        $createdAtDates = $statesGroupedByDate->keys()->toArray();

        if (is_null($executionDate) && !empty($createdAtDates)) {
            $executionDate = Carbon::parse($createdAtDates[0]);
        }

        return view('optimization-overview', [
            'title' => 'Optimization Overview',
            'optimizationDate' => $optimizationDate->toDateString(),
            'executionDate' => $executionDate?->toDateString(),
            'prevDate' => $optimizationDate->clone()->subDay()->toDateString(),
            'nextDate' => $optimizationDate->clone()->addDay()->toDateString(),
            'selectedOfficeId' => $officeId,
            'offices' => $this->getOffices(),
            'createdAtDates' => $createdAtDates,
            'states' => $statesGroupedByDate,
        ]);
    }

    /**
     * GET /optimization/map
     */
    public function optimizationMap(OptimizationMapRequest $request): View
    {
        $state = $this->optimizationDataService->getSingleStateData($request->integer('state_id'));

        // TODO: If state is not found

        return view('optimization-map', [
            'title' => 'Optimization Map',
            'state' => $state,
        ]);
    }

    /**
     * GET /optimization/details
     */
    public function optimizationDetails(OptimizationDetailsRequest $request): View
    {
        $state = $this->optimizationDataService->getOptimizationStateDetails($request->integer('state_id'));

        return view('optimization-details', [
            'title' => 'Optimization Details',
            'state' => $state,
            'simStateId' => $request->integer('sim_state_id'),
        ]);
    }

    /**
     * GET /optimization/sandbox
     */
    public function optimizationSandbox(OptimizationSandboxRequest $request): View
    {
        $optimizationDate = !empty($request->optimization_date)
            ? Carbon::parse($request->optimization_date)
            : Carbon::now();
        $selectedOfficeId = !empty($request->office_id) ? (int) $request->office_id : self::DEFAULT_OFFICE_ID;

        try {
            $state = $this->optimizationSandboxService->getStateForOverview($selectedOfficeId, $optimizationDate);
            $formattedRoutes = $state->getRoutes()->map(fn (Route $route) => $this->routeArrayFormatter->format($route))->toArray();
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }

        return view('optimization-sandbox', [
            'title' => 'Optimization Sandbox',
            'optimizationDate' => $optimizationDate->toDateString(),
            'executionDate' => Carbon::now()->toDateString(),
            'prevDate' => $optimizationDate->clone()->subDay()->toDateString(),
            'nextDate' => $optimizationDate->clone()->addDay()->toDateString(),
            'selectedOfficeId' => $selectedOfficeId,
            'offices' => $this->getOffices(),
            'state' => $state ?? null,
            'formattedRoutes' => $formattedRoutes ?? [],
            'error' => $error ?? null,
        ]);
    }

    /**
     * GET /optimization/simulation
     */
    public function optimizationSimulation(OptimizationSimulationRequest $request): View
    {
        $states = $this->optimizationDataService->getOptimizationStateWithSimulations($request->integer('state_id'));

        return view('optimization-simulation', [
            'title' => 'Optimization Simulations',
            'states' => $states,
        ]);
    }

    /**
     * POST /optimization/simulation/run
     */
    public function optimizationSimulationRun(OptimizationSimulationRunRequest $request): RedirectResponse
    {
        $rules = $request->rule_name ?? [];
        $triggers = array_keys($request->rule_trigger ?? []);
        $disabledRules = array_values(array_diff($rules, $triggers));

        OptimizeRoutesSimulationJob::dispatch(
            sourceStateId: $request->integer('state_id'),
            disabledRules: $disabledRules,
        );

        return back()->withInput()->with('results', 'Optimization Job has been dispatched. Please refresh the page in a few minutes.');
    }

    private function getOffices(): Collection
    {
        return $this->officesQuery
            ->get()
            ->sortBy(fn (Office $office) => $office->getName())
            ->mapWithKeys(fn (Office $office) => [$office->getId() => $office->getName()]);
    }
}
