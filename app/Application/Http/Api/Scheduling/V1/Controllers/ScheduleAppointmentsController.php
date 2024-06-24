<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Scheduling\V1\Controllers;

use App\Application\DTO\ScheduleAppointmentsDTO;
use App\Application\Http\Api\Scheduling\V1\Requests\ScheduleAppointmentsRequest;
use App\Application\Http\Api\Scheduling\V1\Responses\ScheduleAppointmentsResponse;
use App\Application\Http\Responses\NotFoundResponse;
use App\Application\Managers\ScheduleAppointmentsManager;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ScheduleAppointmentsController extends Controller
{
    /**
     * POST /api/v1/scheduling/schedule-appointments-jobs
     *
     * Handles the request to execute schedule appointments job for a given date and office.
     *
     * @param ScheduleAppointmentsRequest $request
     * @param ScheduleAppointmentsManager $manager
     *
     * @return JsonResponse
     */
    public function __invoke(ScheduleAppointmentsRequest $request, ScheduleAppointmentsManager $manager): JsonResponse
    {
        try {
            $manager->manage(new ScheduleAppointmentsDTO(
                array_map(fn (int|string $officeId) => (int) $officeId, $request->office_ids),
                !empty($request->start_date) ? Carbon::parse($request->start_date) : null,
                $request->integer('num_days_after_start_date'),
                $request->integer('num_days_to_schedule'),
                $request->boolean('run_subsequent_optimization'),
            ));
        } catch (OfficeNotFoundException $exception) {
            return new NotFoundResponse($exception->getMessage());
        }

        return new ScheduleAppointmentsResponse();
    }
}
