<?php

declare(strict_types=1);

namespace Tests\Tools\Factories\RouteOptimization;

use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;
use Tests\Tools\Factories\AbstractFactory;

class RuleExecutionResultFactory extends AbstractFactory
{
    public function single($overrides = []): RuleExecutionResult
    {
        return new RuleExecutionResult(
            $overrides['id'] ?? $this->faker->word(),
            $overrides['name'] ?? $this->faker->word(),
            $overrides['description'] ?? $this->faker->sentence,
            $overrides['triggered'] ?? $this->faker->boolean,
            $overrides['applied'] ?? $this->faker->boolean
        );
    }
}
