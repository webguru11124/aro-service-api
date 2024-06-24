<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Tracking\V1\Controllers;

use App\Application\Http\Api\Tracking\V1\Requests\FleetRoutesRequest;
use App\Application\Http\Api\Tracking\V1\Responses\FleetRouteResponse;
use App\Application\Http\Responses\AbstractResponse;
use App\Domain\Contracts\Queries\Office\GetOfficeQuery;
use App\Domain\Contracts\Repositories\TreatmentStateRepository;
use App\Domain\Tracking\Factories\TreatmentStateFactory;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use Carbon\Carbon;
use Illuminate\Routing\Controller;

class FleetRoutesController extends Controller
{
    public function __construct(
        private readonly GetOfficeQuery $officeQuery,
        private readonly TreatmentStateFactory $treatmentStateFactory,
        private readonly TreatmentStateRepository $treatmentStateRepository,
    ) {
    }

    /**
     * GET /api/v1/tracking/fleet-routes
     *
     * Returns a collection of FleetRoute entities
     *
     * @param FleetRoutesRequest $request
     *
     * @return AbstractResponse
     * @throws OfficeNotFoundException
     */
    public function __invoke(FleetRoutesRequest $request): AbstractResponse
    {
        $officeId = $request->integer('office_id');
        $office = $this->officeQuery->get($officeId);

        $dateInOfficeTimezone = Carbon::parse($request->get('date'), $office->getTimezone());
        $state = $this->treatmentStateFactory->create($office, $dateInOfficeTimezone);
        $this->treatmentStateRepository->save($state);

        return new FleetRouteResponse($state);
    }
}
