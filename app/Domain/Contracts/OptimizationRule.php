<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface OptimizationRule
{
    /**
     * Returns a unique identifier for the rule
     */
    public function id(): string;

    /**
     * Returns a Human Readable name for the rule
     */
    public function name(): string;

    /**
     * Returns a Human Readable description for the rule
     * The description should explain in detail what the rule does and why
     */
    public function description(): string;
}
