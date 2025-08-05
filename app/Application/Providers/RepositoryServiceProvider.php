<?php

declare(strict_types=1);

namespace App\Application\Providers;

use App\Domain\Contracts\Repositories\CalendarEventRepository;
use App\Domain\Contracts\Repositories\CustomerRepository;
use App\Domain\Contracts\Repositories\FleetRouteStateRepository;
use App\Domain\Contracts\Repositories\RescheduledPendingServiceRepository;
use App\Domain\Contracts\Repositories\PendingServiceRepository;
use App\Domain\Contracts\Repositories\ScheduledRouteRepository;
use App\Domain\Contracts\Repositories\ServicedRoutesRepository;
use App\Domain\Contracts\Repositories\ServiceHistoryRepository;
use App\Infrastructure\Repositories\PestRoutes\PestRoutesRescheduledPendingServiceRepository;
use App\Infrastructure\Repositories\PestRoutes\PestRoutesPendingServiceRepository;
use App\Infrastructure\Repositories\PestRoutes\PestRoutesScheduledRouteRepository;
use App\Infrastructure\Repositories\PestRoutes\PestRoutesServicedRoutesRepository;
use App\Infrastructure\Repositories\PestRoutes\PestRoutesServiceHistoryRepository;
use App\Infrastructure\Repositories\Postgres\PostgresCalendarEventRepository;
use App\Infrastructure\Repositories\Postgres\PostgresCustomerRepository;
use App\Infrastructure\Repositories\Postgres\PostgresFleetRouteStateRepository;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        CalendarEventRepository::class => PostgresCalendarEventRepository::class,
        ServiceHistoryRepository::class => PestRoutesServiceHistoryRepository::class,
        FleetRouteStateRepository::class => PostgresFleetRouteStateRepository::class,
        PendingServiceRepository::class => PestRoutesPendingServiceRepository::class,
        ScheduledRouteRepository::class => PestRoutesScheduledRouteRepository::class,
        ServicedRoutesRepository::class => PestRoutesServicedRoutesRepository::class,
        RescheduledPendingServiceRepository::class => PestRoutesRescheduledPendingServiceRepository::class,
        CustomerRepository::class => PostgresCustomerRepository::class,
    ];

    /**
     * @return array<class-string>
     */
    public function provides(): array
    {
        return [
            CalendarEventRepository::class,
            ServiceHistoryRepository::class,
            FleetRouteStateRepository::class,
            PendingServiceRepository::class,
            ScheduledRouteRepository::class,
            ServicedRoutesRepository::class,
            CustomerRepository::class,
        ];
    }
}
