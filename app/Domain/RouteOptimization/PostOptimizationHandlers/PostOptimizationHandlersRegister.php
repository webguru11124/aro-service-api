<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\PostOptimizationHandlers;

use Illuminate\Contracts\Container\BindingResolutionException;

class PostOptimizationHandlersRegister
{
    private const HANDLERS = [
        ReoptimizeRoutes::class,
        RemoveLastBreak::class,
        AddExtraWorkEvents::class,
    ];

    /**
     * @return iterable<PostOptimizationHandler>
     * @throws BindingResolutionException
     */
    public function getHandlers(): iterable
    {
        foreach (self::HANDLERS as $handlerClass) {
            yield app()->make($handlerClass);
        }
    }
}
