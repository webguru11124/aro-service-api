<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Tracking\V1\Controllers;

use App\Application\DTO\ServiceStatsDTO;
use App\Application\Http\Api\Tracking\V1\Requests\ServiceStatsRequest;
use App\Application\Http\Api\Tracking\V1\Responses\ServiceStatsStartedResponse;
use App\Application\Managers\ServiceStatsManager;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ServiceStatsController extends Controller
{
    /**
     * POST /api/v1/service-stats
     *
     * @param ServiceStatsRequest $request
     * @param ServiceStatsManager $manager
     *
     * @return JsonResponse
     */
    public function __invoke(ServiceStatsRequest $request, ServiceStatsManager $manager): JsonResponse
    {
        $officeIds = array_map(fn (int|string $officeId) => (int) $officeId, $request->office_ids);

        $manager->manage(new ServiceStatsDTO(
            $officeIds,
            !empty($request->date) ? Carbon::parse($request->date) : null,
        ));

        return new ServiceStatsStartedResponse();
    }
}
