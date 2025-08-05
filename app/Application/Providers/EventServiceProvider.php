<?php

declare(strict_types=1);

namespace App\Application\Providers;

use App\Application\Events\FinancialReport\FinancialReportJobEnded;
use App\Application\Events\FinancialReport\FinancialReportJobFailed;
use App\Application\Events\FinancialReport\FinancialReportJobStarted;
use App\Application\Events\OptimizationJob\OptimizationJobEnded;
use App\Application\Events\OptimizationJob\OptimizationJobFailed;
use App\Application\Events\OptimizationJob\OptimizationJobFailedToUpdateLockedAppointment;
use App\Application\Events\OptimizationJob\OptimizationJobFinished;
use App\Application\Events\OptimizationJob\OptimizationJobStarted;
use App\Application\Events\OptimizationRuleApplied;
use App\Application\Events\OptimizationSkipped;
use App\Application\Events\OptimizationState\OptimizationStateStored;
use App\Application\Events\OptimizationState\OptimizationStateUpdated;
use App\Application\Events\OptimizationStateMetrics\CollectOptimizationStateMetricsEnded;
use App\Application\Events\OptimizationStateMetrics\CollectOptimizationStateMetricsFailed;
use App\Application\Events\OptimizationStateMetrics\CollectOptimizationStateMetricsStarted;
use App\Application\Events\PestRoutesRequestRetry;
use App\Application\Events\PreferredTechResigned;
use App\Application\Events\RoutesCreationJob\RoutesCreationJobEnded;
use App\Application\Events\RoutesCreationJob\RoutesCreationJobFailed;
use App\Application\Events\RoutesCreationJob\RoutesCreationJobStarted;
use App\Application\Events\RouteExcluded;
use App\Application\Events\ScheduleAppointments\ScheduleAppointmentsJobEnded;
use App\Application\Events\ScheduleAppointments\ScheduleAppointmentsJobFailed;
use App\Application\Events\ScheduleAppointments\ScheduleAppointmentsJobStarted;
use App\Application\Events\SchedulingSkipped;
use App\Application\Events\ScriptFailed;
use App\Application\Events\SendNotifications\SendNotificationsJobEnded;
use App\Application\Events\SendNotifications\SendNotificationsJobFailed;
use App\Application\Events\SendNotifications\SendNotificationsJobStarted;
use App\Application\Events\Vroom\VroomRequestSent;
use App\Application\Events\Vroom\VroomResponseReceived;
use App\Application\Listeners\CollectOptimizationJobMetrics;
use App\Application\Listeners\CollectOptimizationStateMetrics;
use App\Application\Listeners\Log\LogCollectOptimizationStateMetrics;
use App\Application\Listeners\Log\LogFinancialReportJob;
use App\Application\Listeners\Log\LogMotiveData;
use App\Application\Listeners\Log\LogOptimizationJob;
use App\Application\Listeners\Log\LogOptimizationRuleApplying;
use App\Application\Listeners\Log\LogOptimizationState;
use App\Application\Listeners\Log\LogPestroutesRequestRetry;
use App\Application\Listeners\Log\LogRouteCreationJob;
use App\Application\Listeners\Log\LogScheduleAppointmentsJob;
use App\Application\Listeners\Log\LogScriptFailed;
use App\Application\Listeners\Log\LogSendNotificationsJob;
use App\Application\Listeners\Log\LogVroomData;
use App\Application\Listeners\Notifications\SendOptimizationFailedNotification;
use App\Application\Listeners\Notifications\SendOptimizationSkippedNotification;
use App\Application\Listeners\Notifications\SendRoutesExcludedNotification;
use App\Application\Listeners\Notifications\SendSchedulingFailedNotification;
use App\Application\Listeners\Notifications\SendSchedulingSkippedNotification;
use App\Application\Listeners\Notifier\PreferredTechUnavailableNotifier;
use App\Application\Listeners\SendFailedToDataDog;
use App\Infrastructure\Services\Motive\Client\Events\MotiveRequestFailed;
use App\Infrastructure\Services\Motive\Client\Events\MotiveRequestSent;
use App\Infrastructure\Services\Motive\Client\Events\MotiveResponseReceived;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        OptimizationStateUpdated::class => [
            CollectOptimizationStateMetrics::class,
        ],
        OptimizationStateStored::class => [
            LogOptimizationState::class,
        ],

        OptimizationRuleApplied::class => [
            LogOptimizationRuleApplying::class,
        ],

        OptimizationJobStarted::class => [
            LogOptimizationJob::class,
        ],
        OptimizationJobEnded::class => [
            LogOptimizationJob::class,
        ],
        OptimizationJobFailed::class => [
            LogOptimizationJob::class,
            SendFailedToDataDog::class,
            SendOptimizationFailedNotification::class,
        ],
        OptimizationJobFailedToUpdateLockedAppointment::class => [
            LogOptimizationJob::class,
            SendOptimizationFailedNotification::class,
        ],
        OptimizationJobFinished::class => [
            CollectOptimizationJobMetrics::class,
        ],
        OptimizationSkipped::class => [
            SendOptimizationSkippedNotification::class,
        ],

        ScheduleAppointmentsJobStarted::class => [
            LogScheduleAppointmentsJob::class,
        ],
        ScheduleAppointmentsJobEnded::class => [
            LogScheduleAppointmentsJob::class,
        ],
        ScheduleAppointmentsJobFailed::class => [
            LogScheduleAppointmentsJob::class,
            SendFailedToDataDog::class,
            SendSchedulingFailedNotification::class,
        ],
        SchedulingSkipped::class => [
            SendSchedulingSkippedNotification::class,
        ],

        SendNotificationsJobStarted::class => [
            LogSendNotificationsJob::class,
        ],
        SendNotificationsJobEnded::class => [
            LogSendNotificationsJob::class,
        ],
        SendNotificationsJobFailed::class => [
            LogSendNotificationsJob::class,
            SendFailedToDataDog::class,
        ],

        FinancialReportJobStarted::class => [
            LogFinancialReportJob::class,
        ],
        FinancialReportJobEnded::class => [
            LogFinancialReportJob::class,
        ],
        FinancialReportJobFailed::class => [
            LogFinancialReportJob::class,
            SendFailedToDataDog::class,
        ],

        CollectOptimizationStateMetricsStarted::class => [
            LogCollectOptimizationStateMetrics::class,
        ],
        CollectOptimizationStateMetricsEnded::class => [
            LogCollectOptimizationStateMetrics::class,
        ],
        CollectOptimizationStateMetricsFailed::class => [
            LogCollectOptimizationStateMetrics::class,
        ],

        ScriptFailed::class => [
            LogScriptFailed::class,
            SendFailedToDataDog::class,
        ],

        PestRoutesRequestRetry::class => [
            LogPestroutesRequestRetry::class,
        ],

        VroomRequestSent::class => [
            LogVroomData::class,
        ],
        VroomResponseReceived::class => [
            LogVroomData::class,
        ],
        MotiveResponseReceived::class => [
            LogMotiveData::class,
        ],
        MotiveRequestSent::class => [
            LogMotiveData::class,
        ],
        MotiveRequestFailed::class => [
            LogMotiveData::class,
        ],
        PreferredTechResigned::class => [
            PreferredTechUnavailableNotifier::class,
        ],

        RoutesCreationJobStarted::class => [
            LogRouteCreationJob::class,
        ],
        RoutesCreationJobEnded::class => [
            LogRouteCreationJob::class,
        ],
        RoutesCreationJobFailed::class => [
            LogRouteCreationJob::class,
            SendFailedToDataDog::class,
        ],
        RouteExcluded::class => [
            SendRoutesExcludedNotification::class,
        ],
    ];

    /**
     * @inheritDoc
     */
    public function boot(): void
    {
    }
}
