<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Caching\Controllers;

use App\Application\Http\Api\Caching\Requests\RefreshAvailableSpotsCacheRequest;
use App\Application\Http\Api\Caching\Responses\RefreshAvailableSpotsCacheResponse;
use App\Application\Jobs\RefreshAvailableSpotsCacheJob;
use App\Domain\Contracts\Queries\Office\GetAllOfficesQuery;
use App\Domain\SharedKernel\Entities\Office;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class RefreshAvailableSpotsCacheController extends Controller
{
    /**
     * POST /api/v1/caching/refresh-available-spots-cache-jobs
     *
     * @param RefreshAvailableSpotsCacheRequest $request
     * @param GetAllOfficesQuery $officesQuery
     *
     * @return JsonResponse
     */
    public function __invoke(RefreshAvailableSpotsCacheRequest $request, GetAllOfficesQuery $officesQuery): JsonResponse
    {
        $officeIds = $request->get('office_ids', []);

        if (empty($officeIds)) {
            $officeIds = $officesQuery->get()->map(
                fn (Office $office) => $office->getId()
            )->toArray();
        }

        $startDate = !empty($request->get('start_date')) ? Carbon::parse($request->get('start_date')) : null;
        $endDate = !empty($request->get('end_date')) ? Carbon::parse($request->get('end_date')) : null;
        $ttl = $request->integer('ttl') ?: null;

        RefreshAvailableSpotsCacheJob::dispatch($officeIds, $startDate, $endDate, $ttl);

        Log::info(__('messages.caching.refresh_available_spots_cache_job_dispatched'), [
            'office_ids' => $officeIds,
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
        ]);

        return new RefreshAvailableSpotsCacheResponse();
    }
}
