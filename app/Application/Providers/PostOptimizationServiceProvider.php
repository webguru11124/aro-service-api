<?php

declare(strict_types=1);

namespace App\Application\Providers;

use App\Domain\Contracts\Services\OptimizationPostProcessService;
use App\Infrastructure\Services\PestRoutes\PestRoutesOptimizationPostProcessService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class PostOptimizationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        OptimizationPostProcessService::class => PestRoutesOptimizationPostProcessService::class,
    ];

    /**
     * @return array<int, class-string>
     */
    public function provides(): array
    {
        return [
            OptimizationPostProcessService::class,
        ];
    }
}
