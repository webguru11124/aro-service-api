<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\PostOptimizationHandlers\ReoptimizationActions;

use App\Domain\RouteOptimization\Entities\Route;

trait ProcessesOnce
{
    /**
     * @test
     */
    public function it_processes_only_once(): void
    {
        $route = $this->getTestRoute();
        $this->routeOptimizationServiceMock
            ->shouldReceive('optimizeSingleRoute')
            ->once()
            ->andReturn($route);

        $this->action->process($route, self::DEFAUL_ENGINE);
        $this->action->process($route, self::DEFAUL_ENGINE);
    }

    abstract private function getTestRoute(): Route;
}
