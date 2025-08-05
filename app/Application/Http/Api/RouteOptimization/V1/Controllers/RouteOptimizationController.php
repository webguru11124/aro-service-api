<?php

declare(strict_types=1);

namespace App\Application\Http\Api\RouteOptimization\V1\Controllers;

use App\Application\DTO\RouteOptimizationDTO;
use App\Application\Http\Api\RouteOptimization\V1\Requests\OptimizeRoutesRequest;
use App\Application\Http\Api\RouteOptimization\V1\Responses\OptimizationStartedResponse;
use App\Application\Http\Responses\NotFoundResponse;
use App\Application\Managers\RoutesOptimizationManager;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class RouteOptimizationController extends Controller
{
    /**
     * POST /api/v1/route-optimization-jobs
     *
     * @param OptimizeRoutesRequest $request
     * @param RoutesOptimizationManager $manager
     *
     * @return JsonResponse
     */
    public function __invoke(OptimizeRoutesRequest $request, RoutesOptimizationManager $manager): JsonResponse
    {
        $officeIds = array_map(fn (int|string $officeId) => (int) $officeId, $request->office_ids);

        try {
            $manager->manage(new RouteOptimizationDTO(
                $officeIds,
                !empty($request->start_date) ? Carbon::parse($request->start_date) : null,
                (int) ($request->num_days_after_start_date ?? 0),
                (int) ($request->num_days_to_optimize ?? 0),
                (bool) ($request->last_optimization_run ?? false),
                $request->boolean('simulation_run'),
                $request->boolean('build_planned_optimization', true),
                $request->disabled_rules ?? [],
            ));
        } catch (OfficeNotFoundException $exception) {
            return new NotFoundResponse($exception->getMessage());
        }

        return new OptimizationStartedResponse();
    }
}
