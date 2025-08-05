<?php

declare(strict_types=1);

namespace App\Application\Http\Web\ServiceDurationCalculation\Controllers;

use App\Domain\RouteOptimization\ValueObjects\PropertyDetails;
use App\Domain\RouteOptimization\ValueObjects\ServiceDuration;
use App\Application\Http\Web\ServiceDurationCalculation\Requests\ServiceDurationCalculationRequest;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ServiceDurationCalculationController extends Controller
{
    /**
     * GET /service-durations-calculations
     */
    public function index(): View
    {
        return view('service-duration-calculator', [
            'title' => 'Service Duration Calculations',
        ]);
    }

    /**
     * POST /service-durations-calculations
     */
    public function calculate(ServiceDurationCalculationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $propertyDetails = new PropertyDetails(
            $request->float('squareFootageOfLot'),
            $request->float('squareFootageOfHouse'),
            $request->float('squareFootageOfHouse')
        );

        $results = [];
        if (isset($data['calculateServiceDuration'])) {
            $linearFootPerSecond = $request->float('linearFootPerSecond') ?? null;
            $serviceDuration = new ServiceDuration(
                propertyDetails: $propertyDetails,
                customLF: $linearFootPerSecond
            );

            $results = [
                'minimumDuration' => round($serviceDuration->getMinimumDuration()->getTotalSeconds() / 60, 2),
                'maximumDuration' => round($serviceDuration->getMaximumDuration()->getTotalSeconds() / 60, 2),
                'optimumDuration' => round($serviceDuration->getOptimumDuration()->getTotalSeconds() / 60, 2),
            ];
        } elseif (isset($data['calculateLf'])) {
            $actualDuration = $request->float('actualDuration');
            $serviceDuration = new ServiceDuration($propertyDetails, $actualDuration);
            $lf = $serviceDuration->getLFforOptimumDuration();
            $results = ['linearFeetPerSecond' => $lf];
        }

        return back()->withInput()->with('results', $results);
    }
}
