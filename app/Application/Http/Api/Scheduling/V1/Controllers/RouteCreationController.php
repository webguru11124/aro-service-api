<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Scheduling\V1\Controllers;

use App\Application\DTO\RouteCreationDTO;
use App\Application\Http\Api\Scheduling\V1\Requests\RouteCreationRequest;
use App\Application\Http\Api\Scheduling\V1\Responses\RouteCreationStartedResponse;
use App\Application\Http\Responses\NotFoundResponse;
use App\Application\Managers\RoutesCreationManager;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class RouteCreationController extends Controller
{
    /**
     * POST /api/v1/scheduling/route-creation-jobs
     *
     * @param RouteCreationRequest $request
     * @param RoutesCreationManager $manager
     *
     * @return JsonResponse
     */
    public function __invoke(RouteCreationRequest $request, RoutesCreationManager $manager): JsonResponse
    {
        $officeIds = array_map(fn (int|string $officeId) => (int) $officeId, $request->office_ids);

        try {
            $manager->manage(new RouteCreationDTO(
                $officeIds,
                !empty($request->start_date) ? Carbon::parse($request->start_date) : null,
                (int) ($request->num_days_after_start_date ?? 0),
                (int) ($request->num_days_to_create_routes ?? 0),
            ));
        } catch (OfficeNotFoundException $exception) {
            return new NotFoundResponse($exception->getMessage());
        }

        return new RouteCreationStartedResponse();
    }
}
