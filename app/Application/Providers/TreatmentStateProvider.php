<?php

declare(strict_types=1);

namespace App\Application\Providers;

use App\Domain\Contracts\Repositories\TreatmentStateRepository;
use App\Infrastructure\Repositories\Postgres\PostgresTreatmentStateRepository;
use Illuminate\Support\ServiceProvider;

class TreatmentStateProvider extends ServiceProvider
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->app->bind(TreatmentStateRepository::class, PostgresTreatmentStateRepository::class);
    }
}
