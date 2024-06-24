<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\PostOptimizationHandlers\RouteValidators;

use App\Domain\RouteOptimization\Entities\Route;

interface RouteValidator
{
    /**
     * @param Route $route
     *
     * @return bool
     */
    public function validate(Route $route): bool;

    /**
     * @return string
     */
    public static function getViolation(): string;
}
