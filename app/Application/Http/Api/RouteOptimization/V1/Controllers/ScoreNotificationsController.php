<?php

declare(strict_types=1);

namespace App\Application\Http\Api\RouteOptimization\V1\Controllers;

use App\Application\DTO\ScoreNotificationsDTO;
use App\Application\Http\Api\RouteOptimization\V1\Requests\ScoreNotificationsRequest;
use App\Application\Http\Api\RouteOptimization\V1\Responses\ScoreNotificationsResponse;
use App\Application\Http\Responses\NotFoundResponse;
use App\Application\Managers\ScoreNotificationsManager;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ScoreNotificationsController extends Controller
{
    /**
     * POST /api/v1/optimization-score-notification-jobs
     *
     * @param ScoreNotificationsRequest $request
     * @param ScoreNotificationsManager $manager
     *
     * @return JsonResponse
     */
    public function __invoke(ScoreNotificationsRequest $request, ScoreNotificationsManager $manager): JsonResponse
    {
        $dto = new ScoreNotificationsDTO(
            array_map(fn (int|string $officeId) => (int) $officeId, $request->office_ids),
            !empty($request->date) ? Carbon::parse($request->date) : null,
        );

        try {
            $manager->manage($dto);
        } catch (OfficeNotFoundException $exception) {
            return new NotFoundResponse($exception->getMessage());
        }

        return new ScoreNotificationsResponse();
    }
}
