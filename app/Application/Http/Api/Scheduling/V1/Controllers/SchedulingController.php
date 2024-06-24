<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Scheduling\V1\Controllers;

use App\Application\Http\Api\Scheduling\V1\Requests\AvailableSpotsRequest;
use App\Application\Http\Api\Scheduling\V1\Requests\CreateAppointmentRequest;
use App\Application\Http\Api\Scheduling\V1\Requests\RescheduleAppointmentRequest;
use App\Application\Http\Api\Scheduling\V1\Responses\AppointmentCreated;
use App\Application\Http\Api\Scheduling\V1\Responses\AppointmentRescheduled;
use App\Application\Http\Api\Scheduling\V1\Responses\AvailableSpotsResponse;
use App\Application\Http\Responses\NotFoundResponse;
use App\Application\Http\Responses\UnprocessableEntityResponse;
use App\Domain\Contracts\Queries\Office\OfficeQuery;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Infrastructure\Dto\CreateAppointmentDto;
use App\Infrastructure\Dto\FindAvailableSpotsDto;
use App\Infrastructure\Dto\RescheduleAppointmentDto;
use App\Infrastructure\Exceptions\AppointmentNotFoundException;
use App\Infrastructure\Exceptions\IVRSchedulerNotFoundException;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use App\Infrastructure\Exceptions\SpotNotFoundException;
use App\Infrastructure\Services\PestRoutes\Actions\CreateAppointment;
use App\Infrastructure\Services\PestRoutes\Actions\FindAvailableSpots;
use App\Infrastructure\Services\PestRoutes\Actions\RescheduleAppointment;
use App\Infrastructure\Services\PestRoutes\Enums\AppointmentType;
use App\Infrastructure\Services\PestRoutes\Enums\RequestingSource;
use App\Infrastructure\Services\PestRoutes\Enums\ServiceType;
use App\Infrastructure\Services\PestRoutes\Enums\Window;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Exceptions\PestRoutesApiException;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class SchedulingController extends Controller
{
    private const DEFAULT_DATES_RANGE = 14;
    private const DEFAULT_DISTANCE_THRESHOLD_MILES = 5;

    /**
     * GET /api/v1/scheduling/available-spots
     *
     * @param AvailableSpotsRequest $request
     * @param FindAvailableSpots $action
     * @param OfficeQuery $officeQuery
     *
     * @return JsonResponse
     */
    public function availableSpots(
        AvailableSpotsRequest $request,
        FindAvailableSpots $action,
        OfficeQuery $officeQuery
    ): JsonResponse {
        try {
            $office = $officeQuery->get($request->integer('office_id'));
        } catch (OfficeNotFoundException $e) {
            return new NotFoundResponse($e->getMessage());
        }

        $startDate = !empty($request->get('start_date'))
            ? Carbon::parse($request->get('start_date'), $office->getTimeZone())
            : Carbon::tomorrow($office->getTimeZone());

        $endDate = !empty($request->get('end_date'))
            ? Carbon::parse($request->get('end_date'), $office->getTimeZone())
            : $startDate->clone()->addDays(self::DEFAULT_DATES_RANGE);

        $dto = new FindAvailableSpotsDto(
            office: $office,
            coordinate: new Coordinate($request->float('lat'), $request->float('lng')),
            isInitial: $request->boolean('is_initial'),
            responseLimit: $request->integer('limit') ?: null,
            distanceThreshold: $request->integer('distance_threshold', self::DEFAULT_DISTANCE_THRESHOLD_MILES),
            startDate: $startDate,
            endDate: $endDate,
            skipCache: $request->isNoCache()
        );

        return new AvailableSpotsResponse(($action)($dto), $request);
    }

    /**
     * POST /api/v1/scheduling/appointments
     *
     * @param CreateAppointmentRequest $request
     * @param CreateAppointment $action
     * @param OfficeQuery $officeQuery
     *
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     * @throws OfficeNotFoundException
     * @throws IVRSchedulerNotFoundException
     */
    public function create(
        CreateAppointmentRequest $request,
        CreateAppointment $action,
        OfficeQuery $officeQuery
    ): JsonResponse {
        $appointmentType = AppointmentType::from($request->integer('appointment_type'));

        $dto = new CreateAppointmentDto(
            office: $officeQuery->get($request->integer('office_id')),
            customerId: $request->integer('customer_id'),
            spotId: $request->integer('spot_id'),
            subscriptionId: $request->integer('subscription_id'),
            appointmentType: $appointmentType,
            isAroSpot: $request->boolean('is_aro_spot'),
            window: Window::from($request->input('window')),
            requestingSource: RequestingSource::from($request->input('requesting_source')),
            notes: $request->input('notes')
        );

        try {
            $result = ($action)($dto);
        } catch (PestRoutesApiException|SpotNotFoundException $e) {
            return new UnprocessableEntityResponse($e->getMessage());
        }

        return new AppointmentCreated($result, $request->input('execution_sid'));
    }

    /**
     * PUT /api/v1/scheduling/appointments/{id}
     *
     * @param int $id
     * @param RescheduleAppointmentRequest $request
     * @param RescheduleAppointment $action
     * @param OfficeQuery $officeQuery
     *
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     * @throws OfficeNotFoundException
     * @throws IVRSchedulerNotFoundException
     */
    public function reschedule(
        int $id,
        RescheduleAppointmentRequest $request,
        RescheduleAppointment $action,
        OfficeQuery $officeQuery
    ): JsonResponse {
        $serviceType = ServiceType::from($request->integer('current_appt_type'));

        $dto = new RescheduleAppointmentDto(
            office: $officeQuery->get($request->integer('office_id')),
            customerId: $request->integer('customer_id'),
            spotId: $request->integer('spot_id'),
            subscriptionId: $request->integer('subscription_id'),
            appointmentId: $id,
            serviceType: $serviceType,
            isAroSpot: $request->boolean('is_aro_spot'),
            window: Window::from($request->input('window')),
            requestingSource: RequestingSource::from($request->input('requesting_source'))
        );

        try {
            $result = ($action)($dto);
        } catch (AppointmentNotFoundException $e) {
            return new NotFoundResponse($e->getMessage());
        } catch (PestRoutesApiException|SpotNotFoundException $e) {
            return new UnprocessableEntityResponse($e->getMessage());
        }

        return new AppointmentRescheduled($result, $request->input('execution_sid'));
    }
}
