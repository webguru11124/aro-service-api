<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Controllers\Employee;

use App\Application\Http\Api\Calendar\V1\Requests\GetAvatarRequest;
use App\Application\Http\Api\Calendar\V1\Responses\GetAvatarResponse;
use App\Application\Http\Responses\NotFoundResponse;
use App\Application\Services\Calendar\CalendarAvatarService;
use Illuminate\Http\JsonResponse;

class GetAvatarController
{
    /**
     * GET /api/v1/calendar/employees/{external_id}/avatar
     *
     * @param GetAvatarRequest $request
     * @param CalendarAvatarService $avatarService
     *
     * @return JsonResponse
     */
    public function __invoke(GetAvatarRequest $request, CalendarAvatarService $avatarService): JsonResponse
    {
        $avatar = $avatarService->getAvatar($request->external_id);

        if (empty($avatar)) {
            return new NotFoundResponse('Avatar not found');
        }

        return new GetAvatarResponse($avatar);
    }
}
