<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\PostOptimizationRules;

class MustHaveBlockedInsideSales implements PostOptimizationRule
{
    /**
     * @return string
     */
    public function id(): string
    {
        return 'MustHaveBlockedInsideSales';
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Must Have Blocked Inside Sales';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'This rule adds Blocked Inside Sales spots at the end of every route of there are empty ones available.';
    }
}
