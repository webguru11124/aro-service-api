<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Controllers\Events;

use App\Application\Http\Api\Calendar\V1\Responses\EventTypesResponse;
use App\Domain\Calendar\Enums\EventType;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class EventTypesController extends Controller
{
    /**
     * GET /api/v1/calendar/event-types
     *
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        return new EventTypesResponse(collect(EventType::cases()));
    }
}
