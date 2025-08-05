<?php

declare(strict_types=1);

namespace App\Application\Http\Api\RouteOptimization\V1\Controllers;

use App\Application\Http\Api\RouteOptimization\V1\Requests\SplitOptimizationStateRequest;
use App\Application\Http\Api\RouteOptimization\V1\Responses\SplitOptimizationStateResponse;
use App\Application\Jobs\SplitOptimizationStateJob;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class SplitOptimizationStateController extends Controller
{
    /**
     * POST /api/v1/split-optimization-state
     *
     * @param SplitOptimizationStateRequest $request
     *
     * @return JsonResponse
     */
    public function __invoke(SplitOptimizationStateRequest $request): JsonResponse
    {
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $forceUpdate = $request->boolean('force_update');
        $officeIds = array_map(fn (int|string $officeId) => (int) $officeId, $request->office_ids);

        SplitOptimizationStateJob::dispatch(
            $startDate,
            $endDate,
            $officeIds,
            $forceUpdate
        );

        return new SplitOptimizationStateResponse();
    }
}
