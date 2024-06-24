<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\Exceptions;

use Exception;

class OptimizationStateNotFoundException extends Exception
{
    public static function instance(int $id): self
    {
        return new self(__('messages.not_found.serialized_optimization_state', [
            'id' => $id,
        ]));
    }
}
