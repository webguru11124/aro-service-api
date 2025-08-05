<?php

declare(strict_types=1);

use App\Application\Http\Responses\RequestedResourceNotFoundResponse;
use App\Application\Http\Web\Notifications\Controllers\NotificationsController;
use App\Application\Http\Web\OptimizationOverview\Controllers\OptimizationOverviewController;
use App\Application\Http\Web\SchedulingOverview\Controllers\SchedulingPlaygroundController;
use App\Application\Http\Web\SchedulingOverview\Controllers\SchedulingOverviewController;
use App\Application\Http\Web\ServiceDurationCalculation\Controllers\ServiceDurationCalculationController;
use Aptive\Component\Http\HttpStatus;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

Route::get('/healthcheck', static function () {
    return new Response('<b style="color: green">Healthy<b>');
});

Route::group(['prefix' => '/scheduling'], static function () {
    Route::get('/overview', [SchedulingOverviewController::class, 'schedulingOverview'])->name('scheduling-overview');
    Route::get('/map', [SchedulingOverviewController::class, 'schedulingMap'])->name('scheduling-map');
    Route::get('/model', [SchedulingPlaygroundController::class, 'schedulingModel'])->name('scheduling-model');
    Route::get('/executions', [SchedulingOverviewController::class, 'schedulingExecutions'])->name('scheduling-executions');
    Route::get('/export', [SchedulingOverviewController::class, 'schedulingExport'])->name('scheduling-export');
    Route::post('/debug', [SchedulingOverviewController::class, 'schedulingDebug'])->name('scheduling-debug');
});

Route::group(['prefix' => '/optimization'], static function () {
    Route::get('/overview', [OptimizationOverviewController::class, 'optimizationOverview'])->name('optimization-overview');
    Route::get('/executions', [OptimizationOverviewController::class, 'optimizationExecutions'])->name('optimization-executions');
    Route::get('/map', [OptimizationOverviewController::class, 'optimizationMap'])->name('optimization-map');
    Route::get('/details', [OptimizationOverviewController::class, 'optimizationDetails'])->name('optimization-details');
    Route::get('/sandbox', [OptimizationOverviewController::class, 'optimizationSandbox'])->name('optimization-sandbox');
    Route::get('/simulation', [OptimizationOverviewController::class, 'optimizationSimulation'])->name('optimization-simulation');
    Route::post('/simulation/run', [OptimizationOverviewController::class, 'optimizationSimulationRun'])->name('optimization-simulation.run');
});

Route::group(['prefix' => '/service-duration-calculator'], static function () {
    Route::get('/', [ServiceDurationCalculationController::class, 'index'])->name('service-duration-calculations.index');
    Route::post('/', [ServiceDurationCalculationController::class, 'calculate'])->name('service-duration-calculations.calculate');
});

Route::group(['prefix' => '/notifications', 'as' => 'notifications.'], static function () {
    Route::group(['prefix' => '/recipients', 'as' => 'recipients.'], static function () {
        Route::get('/', [NotificationsController::class, 'recipients'])->name('index');
        Route::post('/', [NotificationsController::class, 'addRecipient'])->name('create');
        Route::post('/{recipient_id}/notification-types/{notification_type_id}/{channel}', [NotificationsController::class, 'subscribe'])
            ->name('subscribe');
        Route::delete('/{recipient_id}/notification-types/{notification_type_id}/{channel}', [NotificationsController::class, 'unsubscribe'])
            ->name('unsubscribe');
    });
});

Route::get('/', function () {
    return response(null, HttpStatus::OK);
})->name('home');

Route::any('{any}', function () {
    return new RequestedResourceNotFoundResponse();
})->where('any', '.*');
