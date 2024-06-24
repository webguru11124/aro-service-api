<?php

declare(strict_types=1);

use App\Application\Http\Api\Calendar\V1\Controllers\Employee\GetAvatarController;
use App\Application\Http\Api\Calendar\V1\Controllers\Events\CreateEventController;
use App\Application\Http\Api\Calendar\V1\Controllers\Events\DeleteEventController;
use App\Application\Http\Api\Calendar\V1\Controllers\Event\UpdateEventController;
use App\Application\Http\Api\Calendar\V1\Controllers\Events\EventOverrideController;
use App\Application\Http\Api\Calendar\V1\Controllers\Events\EventTypesController;
use App\Application\Http\Api\Calendar\V1\Controllers\Events\GetEventsController;
use App\Application\Http\Api\Calendar\V1\Controllers\Events\Participants\AddParticipantsEventsController;
use App\Application\Http\Api\Calendar\V1\Controllers\Events\Participants\DeleteParticipantsEventsController;
use App\Application\Http\Api\Calendar\V1\Controllers\Events\Participants\GetParticipantsEventsController;
use App\Application\Http\Api\Calendar\V1\Controllers\GetOfficeEmployeesController;
use App\Application\Http\Api\Caching\Controllers\RefreshAvailableSpotsCacheController;
use App\Application\Http\Api\Reporting\V1\Controllers\UpdateFinancialReportController;
use App\Application\Http\Api\RouteOptimization\V1\Controllers\RouteOptimizationController;
use App\Application\Http\Api\RouteOptimization\V1\Controllers\ScoreNotificationsController;
use App\Application\Http\Api\RouteOptimization\V1\Controllers\SplitOptimizationStateController;
use App\Application\Http\Api\Scheduling\V1\Controllers\RouteCreationController;
use App\Application\Http\Api\Scheduling\V1\Controllers\ScheduleAppointmentsController;
use App\Application\Http\Api\Scheduling\V1\Controllers\SchedulingController;
use App\Application\Http\Api\Tracking\V1\Controllers\FleetRoutesController;
use App\Application\Http\Api\Tracking\V1\Controllers\OfficesController;
use App\Application\Http\Api\Tracking\V1\Controllers\RegionsController;
use App\Application\Http\Api\Tracking\V1\Controllers\ServiceStatsController;
use App\Application\Http\Api\WebHooks\V1\Controllers\UpdateCustomerPropertyDetailsController;
use App\Application\Http\Responses\RequestedResourceNotFoundResponse;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(static function () {

    Route::post('/route-optimization-jobs', RouteOptimizationController::class)
        ->name('route-optimization-jobs.create');

    Route::post('/optimization-score-notification-jobs', ScoreNotificationsController::class)
        ->name('score-notification-jobs.create');

    Route::post('/service-stats', ServiceStatsController::class)
        ->name('service-stats-jobs.create');

    Route::put('/caching/available-spots-cache', RefreshAvailableSpotsCacheController::class)
        ->name('available-spots-cache.refresh');

    Route::group(['middleware' => ['jwt'], 'prefix' => 'calendar', 'as' => 'calendar.'], static function () {
        Route::group(['prefix' => 'events', 'as' => 'events.'], static function () {
            Route::get('/', GetEventsController::class)->name('index');
            Route::post('/', CreateEventController::class)->name('create');
            Route::patch('/{event_id}', UpdateEventController::class)->name('update');
            Route::delete('/{event_id}', DeleteEventController::class)->name('delete');

            Route::put('/{event_id}/overrides', EventOverrideController::class)
                ->where('event_id', '^[1-9][0-9]*$')
                ->name('overrides.update');

            Route::group(['prefix' => '/{event_id}/participants', 'as' => 'participants.'], static function () {
                Route::get('/', GetParticipantsEventsController::class)->name('index');
                Route::put('/', AddParticipantsEventsController::class)->name('create');
                Route::delete('/{participant_id}', DeleteParticipantsEventsController::class)
                    ->name('delete');
            });
        });

        Route::group(['prefix' => 'office', 'as' => 'office.'], static function () {
            Route::get('/{office_id}/employees', GetOfficeEmployeesController::class)
                ->name('employees.index');
        });

        Route::get('/employees/{external_id}/avatar', GetAvatarController::class)->name('avatars.index');

        Route::get('/event-types', EventTypesController::class)->name('event-types.index');
    });

    Route::group(['middleware' => ['jwt'], 'prefix' => 'tracking', 'as' => 'tracking.'], static function () {
        Route::group(['prefix' => 'fleet-routes', 'as' => 'fleet-routes.'], function () {
            Route::get('/', FleetRoutesController::class)->name('index');
        });
        Route::group(['prefix' => 'offices', 'as' => 'offices.'], static function () {
            Route::get('/', OfficesController::class)->name('index');
        });
        Route::group(['prefix' => 'regions', 'as' => 'regions.'], static function () {
            Route::get('/', RegionsController::class)->name('index');
        });
    });

    Route::group(['prefix' => 'webhooks', 'as' => 'webhooks.'], static function () {
        Route::put('/customer-property-details', UpdateCustomerPropertyDetailsController::class)
            ->name('customer-property-details-update');
    });

    Route::group(['prefix' => 'scheduling', 'as' => 'scheduling.'], static function () {
        Route::post('/schedule-appointments-jobs', ScheduleAppointmentsController::class)
            ->name('schedule-appointments-jobs.create');
        Route::get('/available-spots', [SchedulingController::class, 'availableSpots'])
            ->name('available-spots.index');
        Route::post('/appointments', [SchedulingController::class, 'create'])
            ->name('appointments.create');
        Route::put('/appointments/{id}', [SchedulingController::class, 'reschedule'])
            ->whereNumber('id')
            ->name('appointments.reschedule');
        Route::post('/route-creation-jobs', RouteCreationController::class)
            ->name('route-creation-jobs.create');
    });

    Route::group(['prefix' => 'reporting', 'as' => 'reporting.'], static function () {
        Route::post('/financial-report-jobs', UpdateFinancialReportController::class)
            ->name('financial-report-jobs.create');
    });

    Route::post('/split-optimization-state', SplitOptimizationStateController::class)
        ->name('split-optimization-state-jobs.create');
});

Route::any('{any}', function () {
    return new RequestedResourceNotFoundResponse();
})->where('any', '.*');
