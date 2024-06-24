<?php

declare(strict_types=1);

namespace App\Application\Providers;

use App\Domain\RouteOptimization\PostOptimizationRules\MustHaveBlockedInsideSales;
use App\Domain\RouteOptimization\PostOptimizationRules\MustUpdateRouteSummary;
use App\Domain\RouteOptimization\PostOptimizationRules\SetAppointmentEstimatedDuration;
use App\Domain\RouteOptimization\PostOptimizationRules\SetExpectServiceTimeWindow;
use App\Domain\RouteOptimization\PostOptimizationRules\SetStaticTimeWindows;
use App\Infrastructure\Services\PestRoutes\PestRoutesOptimizationPostProcessService;
use Illuminate\Support\ServiceProvider;
use App\Domain\RouteOptimization\PostOptimizationRules\DetectRescheduledConfirmedAppointments;

class PostOptimizationRulesProvider extends ServiceProvider
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $rules = [
            $this->app->make(MustHaveBlockedInsideSales::class),
            $this->app->make(MustUpdateRouteSummary::class),
            $this->app->make(SetExpectServiceTimeWindow::class),
            $this->app->make(SetStaticTimeWindows::class),
            $this->app->make(SetAppointmentEstimatedDuration::class),
            $this->app->make(DetectRescheduledConfirmedAppointments::class),
        ];
        $this->app->when(PestRoutesOptimizationPostProcessService::class)
            ->needs('$postProcessRules')
            ->give(fn ($app) => $rules);
    }
}
