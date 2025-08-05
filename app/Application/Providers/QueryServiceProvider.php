<?php

declare(strict_types=1);

namespace App\Application\Providers;

use App\Domain\Contracts\Queries\CustomerPropertyDetailsQuery;
use App\Domain\Contracts\Queries\EmployeeInfoQuery;
use App\Domain\Contracts\Queries\EventParticipantQuery;
use App\Domain\Contracts\Queries\FinancialReportQuery;
use App\Domain\Contracts\Queries\GetEventsOnDateQuery;
use App\Domain\Contracts\Queries\GetRoutesByOfficeAndDateQuery;
use App\Domain\Contracts\Queries\GetRouteTemplateQuery;
use App\Domain\Contracts\Queries\HistoricalAppointmentsQuery;
use App\Domain\Contracts\Queries\Office\GetAllOfficesQuery;
use App\Domain\Contracts\Queries\Office\GetOfficeQuery;
use App\Domain\Contracts\Queries\Office\GetOfficesByIdsQuery;
use App\Domain\Contracts\Queries\Office\OfficeEmployeeQuery;
use App\Domain\Contracts\Queries\Office\OfficeQuery;
use App\Domain\Contracts\Queries\Office\OfficesByIdsQuery;
use App\Domain\Contracts\Queries\PlansQuery;
use App\Infrastructure\Queries\PestRoutes\PestRoutesEventParticipantQuery;
use App\Infrastructure\Queries\PestRoutes\PestRoutesGetRoutesByOfficeAndDateQuery;
use App\Infrastructure\Queries\PestRoutes\PestRoutesGetRouteTemplateQuery;
use App\Infrastructure\Queries\PestRoutes\PestRoutesHistoricalAppointmentsQuery;
use App\Infrastructure\Queries\PestRoutes\PestRoutesOfficeEmployeeQuery;
use App\Infrastructure\Queries\PestRoutes\PestRoutesOfficeQuery;
use App\Infrastructure\Queries\PestRoutes\PestRoutesOfficesByIdsQuery;
use App\Infrastructure\Queries\Postgres\PostgresCustomerPropertyDetailsQuery;
use App\Infrastructure\Queries\Postgres\PostgresGetEventsOnDateQuery;
use App\Infrastructure\Queries\Static\Office\StaticGetAllOfficesQuery;
use App\Infrastructure\Queries\Static\Office\StaticGetOfficeQuery;
use App\Infrastructure\Queries\Static\Office\StaticGetOfficesByIdsQuery;
use App\Infrastructure\Queries\Static\StaticPlansQuery;
use App\Infrastructure\Services\Workday\Queries\WorkdayEmployeeInfoQuery;
use App\Infrastructure\Services\Workday\Queries\WorkdayFinancialReportQuery;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class QueryServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        PlansQuery::class => StaticPlansQuery::class,
        GetOfficeQuery::class => StaticGetOfficeQuery::class,
        GetOfficesByIdsQuery::class => StaticGetOfficesByIdsQuery::class,
        GetAllOfficesQuery::class => StaticGetAllOfficesQuery::class,
        GetEventsOnDateQuery::class => PostgresGetEventsOnDateQuery::class,
        CustomerPropertyDetailsQuery::class => PostgresCustomerPropertyDetailsQuery::class,
        HistoricalAppointmentsQuery::class => PestRoutesHistoricalAppointmentsQuery::class,
        EventParticipantQuery::class => PestRoutesEventParticipantQuery::class,
        OfficeEmployeeQuery::class => PestRoutesOfficeEmployeeQuery::class,
        OfficeQuery::class => PestRoutesOfficeQuery::class,
        OfficesByIdsQuery::class => PestRoutesOfficesByIdsQuery::class,
        FinancialReportQuery::class => WorkdayFinancialReportQuery::class,
        EmployeeInfoQuery::class => WorkdayEmployeeInfoQuery::class,
        GetRouteTemplateQuery::class => PestRoutesGetRouteTemplateQuery::class,
        GetRoutesByOfficeAndDateQuery::class => PestRoutesGetRoutesByOfficeAndDateQuery::class,
    ];

    /**
     * @return array<class-string>
     */
    public function provides(): array
    {
        return [
            PlansQuery::class,
            GetOfficeQuery::class,
            GetOfficesByIdsQuery::class,
            GetAllOfficesQuery::class,
            GetEventsOnDateQuery::class,
            CustomerPropertyDetailsQuery::class,
            HistoricalAppointmentsQuery::class,
            EventParticipantQuery::class,
            OfficeEmployeeQuery::class,
            OfficeQuery::class,
            OfficesByIdsQuery::class,
            FinancialReportQuery::class,
            EmployeeInfoQuery::class,
            GetRouteTemplateQuery::class,
            GetRoutesByOfficeAndDateQuery::class,
        ];
    }
}
