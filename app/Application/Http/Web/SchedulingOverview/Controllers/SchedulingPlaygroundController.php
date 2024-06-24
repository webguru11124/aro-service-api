<?php

declare(strict_types=1);

namespace App\Application\Http\Web\SchedulingOverview\Controllers;

use App\Application\Http\Web\SchedulingOverview\Requests\SchedulingPlaygroundRequest;
use App\Infrastructure\Services\SchedulingDataService;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class SchedulingPlaygroundController extends Controller
{
    public function __construct(
        private SchedulingDataService $schedulingDataService,
    ) {
    }

    /**
     * GET /scheduling/model
     */
    public function schedulingModel(SchedulingPlaygroundRequest $request): View
    {
        $data = !empty($request->state_id)
            ? $this->schedulingDataService->getSchedulingStateInitialData($request->integer('state_id'))
            : [];

        $data['title'] = 'Scheduling Model';

        return view('scheduling-model', $data);
    }
}
