<?php

declare(strict_types=1);

namespace App\Application\Providers;

use App\Domain\Contracts\OptimizationStatePersister;
use App\Domain\Contracts\Repositories\OptimizationStateRepository;
use App\Domain\Contracts\OptimizationStateResolver;
use App\Infrastructure\Formatters\OptimizationStateArrayFormatter;
use App\Infrastructure\Formatters\OptimizationStateFormatter;
use App\Infrastructure\Repositories\Postgres\PostgresOptimizationStateRepository;
use App\Infrastructure\Services\PestRoutes\PestRoutesOptimizationStatePersister;
use App\Infrastructure\Services\PestRoutes\PestRoutesOptimizationStateResolver;
use Illuminate\Support\ServiceProvider;

class OptimizationStateProvider extends ServiceProvider
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->app->bind(OptimizationStateResolver::class, PestRoutesOptimizationStateResolver::class);
        $this->app->bind(OptimizationStatePersister::class, PestRoutesOptimizationStatePersister::class);

        // Repository
        $this->app->bind(OptimizationStateRepository::class, PostgresOptimizationStateRepository::class);

        // Formatter
        $this->app->bind(OptimizationStateFormatter::class, OptimizationStateArrayFormatter::class);
    }
}
