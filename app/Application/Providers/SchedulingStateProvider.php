<?php

declare(strict_types=1);

namespace App\Application\Providers;

use App\Domain\Contracts\Repositories\SchedulingStateRepository;
use App\Infrastructure\Repositories\Postgres\PostgresSchedulingStateRepository;
use Illuminate\Support\ServiceProvider;

class SchedulingStateProvider extends ServiceProvider
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->app->bind(SchedulingStateRepository::class, PostgresSchedulingStateRepository::class);
    }
}
