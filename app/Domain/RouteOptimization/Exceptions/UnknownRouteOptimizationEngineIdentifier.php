<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Exceptions;

use Exception;

class UnknownRouteOptimizationEngineIdentifier extends Exception
{
    /**
     * @param string $engine
     *
     * @return UnknownRouteOptimizationEngineIdentifier
     */
    public static function instance(string $engine): UnknownRouteOptimizationEngineIdentifier
    {
        return new self(__('messages.routes_optimization.unknown_engine_identifier', [
            'identifier' => $engine,
        ]));
    }
}
