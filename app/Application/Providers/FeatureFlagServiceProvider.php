<?php

declare(strict_types=1);

namespace App\Application\Providers;

use App\Domain\Contracts\FeatureFlagService;
use App\Infrastructure\Services\ConfigCat\ConfigCatService;
use ConfigCat\ClientInterface;
use ConfigCat\ConfigCatClient;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class FeatureFlagServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        ClientInterface::class => ConfigCatClient::class,
        FeatureFlagService::class => ConfigCatService::class,
    ];

    /**
     * @return array<int, class-string>
     */
    public function provides(): array
    {
        return [
            FeatureFlagService::class,
            ClientInterface::class,
        ];
    }
}
