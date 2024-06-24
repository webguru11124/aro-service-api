<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes;

use Aptive\PestRoutesSDK\Client as PestRoutesClient;

trait PestRoutesClientAware
{
    public function __construct(private PestRoutesClient $client)
    {
    }

    protected function getClient(): PestRoutesClient
    {
        return $this->client;
    }
}
