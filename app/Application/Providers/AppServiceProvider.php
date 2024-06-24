<?php

declare(strict_types=1);

namespace App\Application\Providers;

use App\Infrastructure\Formatters\NotificationFormatter;
use App\Domain\Contracts\Services\RouteCompletionStatsService;
use App\Domain\Contracts\Services\RouteDrivingStatsService;
use App\Domain\Contracts\Services\RouteOptimizationService;
use App\Domain\Contracts\Services\HRService;
use App\Domain\Contracts\Services\VehicleTrackingDataService;
use App\Domain\Contracts\Services\Actions\ReserveTimeForCalendarEvents;
use App\Infrastructure\Instrumentation\Datadog\Instrument;
use App\Infrastructure\Formatters\NotificationMessageFormatter;
use App\Infrastructure\Services\PestRoutes\Actions\PestRoutesReserveTimeForCalendarEvents;
use App\Infrastructure\Services\Google\GoogleRouteOptimizationService;
use App\Infrastructure\Services\Motive\MotiveRouteDrivingDataService;
use App\Infrastructure\Services\Motive\MotiveVehicleTrackingDataService;
use App\Infrastructure\Services\PestRoutes\PestRoutesRouteCompletionStatsService;
use App\Infrastructure\Services\Vroom\VroomRouteOptimizationService;
use App\Infrastructure\Services\Workday\WorkdayService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        RouteDrivingStatsService::class => MotiveRouteDrivingDataService::class,
        VehicleTrackingDataService::class => MotiveVehicleTrackingDataService::class,
        RouteCompletionStatsService::class => PestRoutesRouteCompletionStatsService::class,
        HRService::class => WorkdayService::class,
        NotificationFormatter::class => NotificationMessageFormatter::class,
        ReserveTimeForCalendarEvents::class => PestRoutesReserveTimeForCalendarEvents::class,
    ];

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            Instrument::traceOptimizeRoutesJob();
        }
    }

    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(RouteOptimizationService::class, fn () => [
            $this->app->make(VroomRouteOptimizationService::class),
            $this->app->make(GoogleRouteOptimizationService::class),
        ]);
    }

    /**
     * @return array<int, class-string>
     */
    public function provides(): array
    {
        return [
            RouteOptimizationService::class,
            RouteDrivingStatsService::class,
            VehicleTrackingDataService::class,
            RouteCompletionStatsService::class,
            HRService::class,
            NotificationFormatter::class,
            ReserveTimeForCalendarEvents::class,
        ];
    }
}
