<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Controllers\Events;

use App\Application\Http\Api\Calendar\V1\Requests\GetEventRequest;
use App\Application\Http\Api\Calendar\V1\Responses\GetEventsResponse;
use App\Domain\Calendar\Actions\SearchEvents;
use App\Domain\Calendar\Actions\SearchEventsParams;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class GetEventsController extends Controller
{
    /**
     * GET /api/v1/calendar/events
     *
     * @param GetEventRequest $request
     *
     * @return JsonResponse
     */
    public function __invoke(GetEventRequest $request, SearchEvents $action): JsonResponse
    {
        $dto = new SearchEventsParams(
            startDate: $request->date('start_date'),
            endDate: $request->date('end_date'),
            officeId: $request->integer('office_id') ?: null,
            searchText: $request->get('search_text')
        );
        $page = $request->integer('page') ?: null;
        $perPage = $request->integer('per_page') ?: null;

        return new GetEventsResponse(($action)($dto), $page, $perPage);
    }
}
