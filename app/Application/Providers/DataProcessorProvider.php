<?php

declare(strict_types=1);

namespace App\Application\Providers;

use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\CustomersDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesCustomersDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\SubscriptionsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesSubscriptionsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers\PestRoutesAppointmentsDataProcessorCacheWrapper;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers\PestRoutesEmployeesDataProcessorCacheWrapper;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers\PestRoutesOfficesDataProcessorCacheWrapper;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers\PestRoutesRouteTemplatesDataProcessorCacheWrapper;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers\PestRoutesServiceTypesDataProcessorCacheWrapper;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentRemindersDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\AppointmentsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\CachableAppointmentsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\EmployeesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\OfficesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\RoutesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\RouteTemplatesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\ServiceTypesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\SpotsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesAppointmentRemindersDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesAppointmentsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesRoutesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesSpotsDataProcessor;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class DataProcessorProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        OfficesDataProcessor::class => PestRoutesOfficesDataProcessorCacheWrapper::class,
        SpotsDataProcessor::class => PestRoutesSpotsDataProcessor::class,
        AppointmentsDataProcessor::class => PestRoutesAppointmentsDataProcessor::class,
        CachableAppointmentsDataProcessor::class => PestRoutesAppointmentsDataProcessorCacheWrapper::class,
        EmployeesDataProcessor::class => PestRoutesEmployeesDataProcessorCacheWrapper::class,
        RoutesDataProcessor::class => PestRoutesRoutesDataProcessor::class,
        AppointmentRemindersDataProcessor::class => PestRoutesAppointmentRemindersDataProcessor::class,
        ServiceTypesDataProcessor::class => PestRoutesServiceTypesDataProcessorCacheWrapper::class,
        CustomersDataProcessor::class => PestRoutesCustomersDataProcessor::class,
        SubscriptionsDataProcessor::class => PestRoutesSubscriptionsDataProcessor::class,
        RouteTemplatesDataProcessor::class => PestRoutesRouteTemplatesDataProcessorCacheWrapper::class,
    ];

    /**
     * @return array<class-string>
     */
    public function provides(): array
    {
        return [
            OfficesDataProcessor::class,
            SpotsDataProcessor::class,
            AppointmentsDataProcessor::class,
            EmployeesDataProcessor::class,
            RoutesDataProcessor::class,
            AppointmentRemindersDataProcessor::class,
            ServiceTypesDataProcessor::class,
            CachableAppointmentsDataProcessor::class,
            CustomersDataProcessor::class,
            SubscriptionsDataProcessor::class,
        ];
    }
}
