<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Controllers;

use App\Application\Http\Api\Calendar\V1\Requests\GetOfficeEmployeesRequest;
use App\Application\Http\Api\Calendar\V1\Responses\GetOfficeEmployeesResponse;
use App\Application\Http\Responses\NotFoundResponse;
use App\Domain\Contracts\Queries\Office\OfficeEmployeeQuery;
use Aptive\PestRoutesSDK\Exceptions\ResourceNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class GetOfficeEmployeesController extends Controller
{
    /**
     * GET /api/v1/calendar/office/{office_id}/employees
     *
     * @param GetOfficeEmployeesRequest $request
     * @param OfficeEmployeeQuery $officeEmployeeQuery
     *
     * @return JsonResponse
     */
    public function __invoke(GetOfficeEmployeesRequest $request, OfficeEmployeeQuery $officeEmployeeQuery): JsonResponse
    {
        $officeId = (int) $request->office_id;

        try {
            $employees = $officeEmployeeQuery->find($officeId);
        } catch (ResourceNotFoundException $exception) {
            return new NotFoundResponse($exception->getMessage());
        }

        return new GetOfficeEmployeesResponse($employees);
    }
}
