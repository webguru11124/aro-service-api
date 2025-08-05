<?php

declare(strict_types=1);

namespace App\Application\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(static function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'))
            ;
            Route::middleware('web')
                ->group(base_path('routes/web.php'))
            ;
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for(
            'api',
            static fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip())
        );
    }
}
